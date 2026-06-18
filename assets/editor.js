(function (wp) {
	'use strict';

	if (!wp || !wp.plugins || !wp.element || !wp.components || !wp.apiFetch || !wp.data || !wp.blocks) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var __ = wp.i18n.__;
	var registerPlugin = wp.plugins.registerPlugin;
	var registerBlockType = wp.blocks.registerBlockType;
	var createBlock = wp.blocks.createBlock;
	var apiFetch = wp.apiFetch;
	var blockEditor = wp.blockEditor || wp.editor;
	var editPost = wp.editPost || {};
	var editorPackage = wp.editor || {};
	var PluginSidebar = editorPackage.PluginSidebar || editPost.PluginSidebar;
	var PluginSidebarMoreMenuItem = editorPackage.PluginSidebarMoreMenuItem || editPost.PluginSidebarMoreMenuItem;
	var components = wp.components;
	var Button = components.Button;
	var Notice = components.Notice;
	var PanelBody = components.PanelBody;
	var Placeholder = components.Placeholder;
	var SelectControl = components.SelectControl;
	var Spinner = components.Spinner;
	var TextareaControl = components.TextareaControl;
	var TextControl = components.TextControl;
	var settings = window.TTFWAI || {};

	function escapeHtml(value) {
		return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
	}

	function stripHtml(value) {
		var wrapper = document.createElement('div');
		wrapper.innerHTML = String(value || '');
		return wrapper.textContent || wrapper.innerText || '';
	}

	function limit(value, maxLength) {
		value = String(value || '');
		return value.length > maxLength ? value.substring(0, maxLength) : value;
	}

	function selectedBlockContext() {
		var select = wp.data.select('core/block-editor');
		var editorSelect = wp.data.select('core/editor');
		var selectedIds = select.getSelectedBlockClientIds ? select.getSelectedBlockClientIds() : [];
		var parts = [];
		var title = editorSelect && editorSelect.getEditedPostAttribute ? editorSelect.getEditedPostAttribute('title') : '';

		if (title) {
			parts.push('Post title: ' + stripHtml(title));
		}

		if (!selectedIds || !selectedIds.length) {
			return limit(parts.join('\n\n'), 12000);
		}

		selectedIds.forEach(function (clientId) {
			var text = blockToText(select.getBlock(clientId));
			if (text) {
				parts.push(text);
			}
		});

		return limit(parts.join('\n\n'), 12000);
	}

	function blockToText(block) {
		var attrs;
		var parts = [];

		if (!block) {
			return '';
		}

		attrs = block.attributes || {};
		['content', 'value', 'text', 'caption', 'citation'].forEach(function (key) {
			if (attrs[key]) {
				parts.push(stripHtml(attrs[key]));
			}
		});

		if (block.innerBlocks && block.innerBlocks.length) {
			block.innerBlocks.forEach(function (innerBlock) {
				var innerText = blockToText(innerBlock);
				if (innerText) {
					parts.push(innerText);
				}
			});
		}

		if (!parts.length && Object.keys(attrs).length) {
			parts.push(JSON.stringify(attrs));
		}

		return parts.join('\n').trim();
	}

	function textToParagraphBlocks(text) {
		var paragraphs = String(text || '').replace(/\r\n/g, '\n').split(/\n{2,}/).map(function (paragraph) {
			return paragraph.trim();
		}).filter(Boolean);

		if (!paragraphs.length) {
			paragraphs = [String(text || '').trim()];
		}

		return paragraphs.map(function (paragraph) {
			return createBlock('core/paragraph', {content: escapeHtml(paragraph).replace(/\n/g, '<br>')});
		});
	}

	function textToHtml(text) {
		return textToParagraphBlocks(text).map(function (block) {
			return '<p>' + block.attributes.content + '</p>';
		}).join('\n');
	}

	function useAiState(initialPrompt) {
		var defaultProvider = settings.defaultProvider || 'tools';
		return {
			provider: useState(defaultProvider),
			prompt: useState(initialPrompt || ''),
			persona: useState(settings.defaultPersona || ''),
			model: useState(defaultProvider === 'openai' ? (settings.openaiModel || '') : (settings.toolsModel || '')),
			result: useState(''),
			error: useState(''),
			isLoading: useState(false)
		};
	}

	function runAi(state) {
		var provider = state.provider[0];
		var prompt = state.prompt[0];
		var setResult = state.result[1];
		var setError = state.error[1];
		var setIsLoading = state.isLoading[1];

		setError('');
		setResult('');

		if (!String(prompt || '').trim()) {
			setError(__('Write an instruction first.', 'tornevall-tools-for-wordpress'));
			return Promise.resolve();
		}

		setIsLoading(true);

		return apiFetch({
			path: settings.endpoint || '/ttfw/v1/ai/respond',
			method: 'POST',
			data: {
				provider: provider,
				prompt: prompt,
				context: selectedBlockContext(),
				persona: state.persona[0],
				model: state.model[0],
				response_language: settings.responseLanguage || 'auto'
			}
		}).then(function (response) {
			setResult(response && response.text ? response.text : '');
		}).catch(function (error) {
			setError(error && error.message ? error.message : __('The AI request failed.', 'tornevall-tools-for-wordpress'));
		}).finally(function () {
			setIsLoading(false);
		});
	}

	function ProviderControls(props) {
		var state = props.state;
		var provider = state.provider[0];
		var setProvider = state.provider[1];
		var setModel = state.model[1];

		return el(Fragment, {},
			el(SelectControl, {
				label: __('Provider', 'tornevall-tools-for-wordpress'),
				value: provider,
				options: [
					{label: __('Tornevall Tools AI', 'tornevall-tools-for-wordpress'), value: 'tools'},
					{label: __('OpenAI direct', 'tornevall-tools-for-wordpress'), value: 'openai'}
				],
				onChange: function (value) {
					setProvider(value);
					setModel(value === 'openai' ? (settings.openaiModel || '') : (settings.toolsModel || ''));
				}
			}),
			el(TextControl, {
				label: __('Model override', 'tornevall-tools-for-wordpress'),
				help: __('Leave blank to use the configured provider default where possible.', 'tornevall-tools-for-wordpress'),
				value: state.model[0],
				onChange: state.model[1]
			})
		);
	}

	function ResultControls(props) {
		var result = props.result;
		var allowInsert = result && blockEditor;

		function replaceSelected() {
			var dispatch = wp.data.dispatch('core/block-editor');
			var select = wp.data.select('core/block-editor');
			var ids = select.getSelectedBlockClientIds ? select.getSelectedBlockClientIds() : [];
			var blocks = textToParagraphBlocks(result);

			if (ids.length) {
				dispatch.replaceBlocks(ids, blocks);
				return;
			}
			dispatch.insertBlocks(blocks);
		}

		function insertAfterSelected() {
			var dispatch = wp.data.dispatch('core/block-editor');
			var select = wp.data.select('core/block-editor');
			var ids = select.getSelectedBlockClientIds ? select.getSelectedBlockClientIds() : [];
			var blocks = textToParagraphBlocks(result);
			var lastId;
			var rootId;
			var index;

			if (!ids.length) {
				dispatch.insertBlocks(blocks);
				return;
			}

			lastId = ids[ids.length - 1];
			rootId = select.getBlockRootClientId ? select.getBlockRootClientId(lastId) : undefined;
			index = select.getBlockIndex ? select.getBlockIndex(lastId, rootId) : undefined;
			dispatch.insertBlocks(blocks, index + 1, rootId);
		}

		if (!result) {
			return null;
		}

		return el('div', {className: 'ttfw-ai-result'},
			el('h3', {}, __('Result', 'tornevall-tools-for-wordpress')),
			el('textarea', {className: 'ttfw-ai-result__textarea', readOnly: true, value: result}),
			el('div', {className: 'ttfw-ai-actions'},
				el(Button, {variant: 'primary', disabled: !allowInsert, onClick: insertAfterSelected}, __('Insert after selection', 'tornevall-tools-for-wordpress')),
				el(Button, {variant: 'secondary', disabled: !allowInsert, onClick: replaceSelected}, __('Replace selection', 'tornevall-tools-for-wordpress'))
			)
		);
	}

	function AssistantPanel(props) {
		var state = props.state;
		var error = state.error[0];
		var result = state.result[0];
		var isLoading = state.isLoading[0];

		return el(Fragment, {},
			el(ProviderControls, {state: state}),
			el(TextareaControl, {
				label: __('Instruction', 'tornevall-tools-for-wordpress'),
				help: __('Selected blocks are sent as context through the server-side proxy.', 'tornevall-tools-for-wordpress'),
				rows: 6,
				value: state.prompt[0],
				onChange: state.prompt[1]
			}),
			el(TextareaControl, {
				label: __('Persona override', 'tornevall-tools-for-wordpress'),
				rows: 5,
				value: state.persona[0],
				onChange: state.persona[1]
			}),
			el('div', {className: 'ttfw-ai-actions'},
				el(Button, {variant: 'primary', disabled: isLoading, onClick: function () { runAi(state); }}, isLoading ? __('Generating...', 'tornevall-tools-for-wordpress') : __('Generate', 'tornevall-tools-for-wordpress')),
				settings.settingsUrl ? el(Button, {variant: 'link', href: settings.settingsUrl, target: '_blank'}, __('Settings', 'tornevall-tools-for-wordpress')) : null
			),
			isLoading ? el('div', {className: 'ttfw-ai-loading'}, el(Spinner, {})) : null,
			error ? el(Notice, {status: 'error', isDismissible: false}, error) : null,
			el(ResultControls, {result: result})
		);
	}

	function SidebarRender() {
		var state = useAiState(__('Improve the selected block content.', 'tornevall-tools-for-wordpress'));

		if (!PluginSidebar) {
			return null;
		}

		return el(Fragment, {},
			PluginSidebarMoreMenuItem ? el(PluginSidebarMoreMenuItem, {target: 'tornevall-ai-sidebar', icon: 'admin-comments'}, __('Tornevall AI', 'tornevall-tools-for-wordpress')) : null,
			el(PluginSidebar, {name: 'tornevall-ai-sidebar', title: __('Tornevall AI', 'tornevall-tools-for-wordpress'), icon: 'admin-comments'},
				el(PanelBody, {title: __('AI assistant', 'tornevall-tools-for-wordpress'), initialOpen: true}, el(AssistantPanel, {state: state}))
			)
		);
	}

	function AssistantBlockEdit(props) {
		var state = useAiState(__('Write a section for this post.', 'tornevall-tools-for-wordpress'));
		var result = state.result[0];

		return el('div', {className: 'ttfw-ai-block'},
			el(Placeholder, {
				icon: 'admin-comments',
				label: __('Tornevall AI Assistant', 'tornevall-tools-for-wordpress'),
				instructions: __('Generate text through Tornevall Tools AI or direct OpenAI. This block is placed in the Text category, next to writing blocks.', 'tornevall-tools-for-wordpress')
			},
				el(AssistantPanel, {state: state}),
				result ? el(Button, {variant: 'secondary', onClick: function () { props.setAttributes({content: textToHtml(result)}); }}, __('Store result in this block', 'tornevall-tools-for-wordpress')) : null
			),
			props.attributes.content ? el('div', {className: 'ttfw-ai-block__preview', dangerouslySetInnerHTML: {__html: props.attributes.content}}) : null
		);
	}

	function AssistantBlockSave(props) {
		if (!props.attributes.content) {
			return null;
		}
		return el(wp.element.RawHTML, {}, props.attributes.content);
	}

	if (PluginSidebar) {
		registerPlugin('tornevall-tools-ai', {render: SidebarRender});
	}

	registerBlockType('tornevall-tools/ai-assistant', {
		title: __('Tornevall AI Assistant', 'tornevall-tools-for-wordpress'),
		description: __('Generate editor text with Tornevall Tools AI or direct OpenAI.', 'tornevall-tools-for-wordpress'),
		icon: 'admin-comments',
		category: 'text',
		keywords: [__('AI', 'tornevall-tools-for-wordpress'), __('OpenAI', 'tornevall-tools-for-wordpress'), __('Tornevall', 'tornevall-tools-for-wordpress')],
		attributes: {
			content: {type: 'string', default: ''}
		},
		edit: AssistantBlockEdit,
		save: AssistantBlockSave
	});
})(window.wp);
