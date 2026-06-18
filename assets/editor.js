(function (wp) {
	'use strict';

	if (!wp || !wp.plugins || !wp.element || !wp.components || !wp.apiFetch || !wp.data || !wp.blocks || !wp.hooks) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var __ = wp.i18n.__;
	var addFilter = wp.hooks.addFilter;
	var registerPlugin = wp.plugins.registerPlugin;
	var registerBlockType = wp.blocks.registerBlockType;
	var createBlock = wp.blocks.createBlock;
	var apiFetch = wp.apiFetch;
	var blockEditor = wp.blockEditor || wp.editor;
	var editPost = wp.editPost || {};
	var editorPackage = wp.editor || {};
	var PluginSidebar = editorPackage.PluginSidebar || editPost.PluginSidebar;
	var PluginSidebarMoreMenuItem = editorPackage.PluginSidebarMoreMenuItem || editPost.PluginSidebarMoreMenuItem;
	var BlockControls = blockEditor && blockEditor.BlockControls;
	var components = wp.components;
	var Button = components.Button;
	var Modal = components.Modal;
	var Notice = components.Notice;
	var PanelBody = components.PanelBody;
	var Placeholder = components.Placeholder;
	var SelectControl = components.SelectControl;
	var Spinner = components.Spinner;
	var TextareaControl = components.TextareaControl;
	var TextControl = components.TextControl;
	var ToolbarDropdownMenu = components.ToolbarDropdownMenu;
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

	function inlineMarkdownToHtml(text) {
		var placeholders = [];
		var html = escapeHtml(text);

		html = html.replace(/`([^`]+)`/g, function (match, code) {
			var token = '%%TTFW_CODE_' + placeholders.length + '%%';
			placeholders.push('<code>' + code + '</code>');
			return token;
		});
		html = html.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+|mailto:[^\s)]+)\)/g, function (match, label, url) {
			return '<a href="' + escapeHtml(url) + '">' + label + '</a>';
		});
		html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
		html = html.replace(/__([^_]+)__/g, '<strong>$1</strong>');
		html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
		html = html.replace(/_([^_]+)_/g, '<em>$1</em>');
		placeholders.forEach(function (value, index) {
			html = html.replace('%%TTFW_CODE_' + index + '%%', value);
		});
		return html;
	}

	function flushParagraph(buffer, blocks) {
		if (buffer.length) {
			blocks.push(createBlock('core/paragraph', {content: inlineMarkdownToHtml(buffer.join(' '))}));
			buffer.length = 0;
		}
	}

	function markdownToBlocks(markdown) {
		var lines = String(markdown || '').replace(/\r\n/g, '\n').split('\n');
		var blocks = [];
		var paragraph = [];
		var index = 0;

		while (index < lines.length) {
			var line = lines[index];
			var trimmed = line.trim();
			var heading;
			var listLines;
			var ordered;
			var quoteLines;
			var codeLines;

			if (!trimmed) {
				flushParagraph(paragraph, blocks);
				index++;
				continue;
			}
			if (/^```/.test(trimmed)) {
				flushParagraph(paragraph, blocks);
				codeLines = [];
				index++;
				while (index < lines.length && !/^```/.test(lines[index].trim())) {
					codeLines.push(lines[index]);
					index++;
				}
				if (index < lines.length) {
					index++;
				}
				blocks.push(createBlock('core/code', {content: escapeHtml(codeLines.join('\n'))}));
				continue;
			}
			if (/^---+$/.test(trimmed) || /^\*\*\*+$/.test(trimmed)) {
				flushParagraph(paragraph, blocks);
				blocks.push(createBlock('core/separator'));
				index++;
				continue;
			}
			heading = trimmed.match(/^(#{1,6})\s+(.+)$/);
			if (heading) {
				flushParagraph(paragraph, blocks);
				blocks.push(createBlock('core/heading', {level: heading[1].length, content: inlineMarkdownToHtml(heading[2])}));
				index++;
				continue;
			}
			if (/^>\s?/.test(trimmed)) {
				flushParagraph(paragraph, blocks);
				quoteLines = [];
				while (index < lines.length && /^>\s?/.test(lines[index].trim())) {
					quoteLines.push(lines[index].trim().replace(/^>\s?/, ''));
					index++;
				}
				blocks.push(createBlock('core/quote', {value: '<p>' + inlineMarkdownToHtml(quoteLines.join(' ')) + '</p>'}));
				continue;
			}
			if (/^(?:[-*+]\s+|\d+\.\s+)/.test(trimmed)) {
				flushParagraph(paragraph, blocks);
				listLines = [];
				ordered = /^\d+\.\s+/.test(trimmed);
				while (index < lines.length && (/^(?:[-*+]\s+|\d+\.\s+)/.test(lines[index].trim()))) {
					listLines.push(lines[index].trim().replace(/^(?:[-*+]\s+|\d+\.\s+)/, ''));
					index++;
				}
				blocks.push(createBlock('core/list', {
					ordered: ordered,
					values: listLines.map(function (item) { return '<li>' + inlineMarkdownToHtml(item) + '</li>'; }).join('')
				}));
				continue;
			}
			paragraph.push(trimmed);
			index++;
		}

		flushParagraph(paragraph, blocks);
		if (!blocks.length) {
			blocks.push(createBlock('core/paragraph', {content: inlineMarkdownToHtml(markdown)}));
		}
		return blocks;
	}

	function markdownToHtml(markdown) {
		return markdownToBlocks(markdown).map(function (block) {
			if (block.name === 'core/paragraph') {
				return '<p>' + block.attributes.content + '</p>';
			}
			if (block.name === 'core/heading') {
				return '<h' + block.attributes.level + '>' + block.attributes.content + '</h' + block.attributes.level + '>';
			}
			if (block.name === 'core/list') {
				return (block.attributes.ordered ? '<ol>' : '<ul>') + block.attributes.values + (block.attributes.ordered ? '</ol>' : '</ul>');
			}
			if (block.name === 'core/quote') {
				return '<blockquote>' + block.attributes.value + '</blockquote>';
			}
			if (block.name === 'core/code') {
				return '<pre><code>' + block.attributes.content + '</code></pre>';
			}
			if (block.name === 'core/separator') {
				return '<hr />';
			}
			return '';
		}).join('\n');
	}

	function useAiState(initialPrompt) {
		var defaultProvider = settings.defaultProvider || 'tools';
		return {
			provider: useState(defaultProvider),
			prompt: useState(initialPrompt || ''),
			customText: useState(''),
			persona: useState(settings.defaultPersona || ''),
			model: useState(defaultProvider === 'openai' ? (settings.openaiModel || '') : (settings.toolsModel || '')),
			result: useState(''),
			error: useState(''),
			uploadWarning: useState(''),
			uploadName: useState(''),
			isLoading: useState(false),
			isUploading: useState(false)
		};
	}

	function defaultModelForProvider(provider) {
		return provider === 'openai' ? (settings.openaiModel || '') : (settings.toolsModel || '');
	}

	function runAiRequest(request) {
		return apiFetch({
			path: settings.endpoint || '/ttfw/v1/ai/respond',
			method: 'POST',
			data: {
				provider: request.provider || settings.defaultProvider || 'tools',
				prompt: request.prompt || '',
				custom_text: request.customText || '',
				context: request.context || '',
				persona: request.persona || settings.defaultPersona || '',
				model: request.model || defaultModelForProvider(request.provider || settings.defaultProvider || 'tools'),
				response_language: settings.responseLanguage || 'auto',
				output_format: request.outputFormat || 'wp_markdown'
			}
		});
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
			setError(__('Write instructions first.', 'tornevall-tools-for-wordpress'));
			return Promise.resolve();
		}
		setIsLoading(true);

		return runAiRequest({
			provider: provider,
			prompt: prompt,
			customText: state.customText[0],
			context: selectedBlockContext(),
			persona: state.persona[0],
			model: state.model[0],
			outputFormat: 'wp_markdown'
		}).then(function (response) {
			setResult(response && response.text ? response.text : '');
		}).catch(function (error) {
			setError(error && error.message ? error.message : __('The AI request failed.', 'tornevall-tools-for-wordpress'));
		}).finally(function () {
			setIsLoading(false);
		});
	}

	function uploadDocument(file, state) {
		var formData;
		var maxBytes = parseInt(settings.uploadMaxBytes || 0, 10);

		if (!file) {
			return;
		}
		state.error[1]('');
		state.uploadWarning[1]('');
		state.uploadName[1]('');
		if (maxBytes && file.size > maxBytes) {
			state.error[1](__('The selected document is too large.', 'tornevall-tools-for-wordpress'));
			return;
		}

		formData = new window.FormData();
		formData.append('file', file);
		state.isUploading[1](true);
		apiFetch({path: settings.documentEndpoint || '/ttfw/v1/document/extract', method: 'POST', body: formData}).then(function (response) {
			state.customText[1](response && response.text ? response.text : '');
			state.uploadName[1](response && response.filename ? response.filename : file.name);
			state.uploadWarning[1](response && response.warning ? response.warning : '');
		}).catch(function (error) {
			state.error[1](error && error.message ? error.message : __('The document could not be read.', 'tornevall-tools-for-wordpress'));
		}).finally(function () {
			state.isUploading[1](false);
		});
	}

	function ProviderControls(props) {
		var state = props.state;
		var provider = state.provider[0];
		var setProvider = state.provider[1];
		var setModel = state.model[1];

		return el('div', {className: 'ttfw-ai-provider-grid'},
			el(SelectControl, {
				label: __('Provider', 'tornevall-tools-for-wordpress'),
				value: provider,
				options: [
					{label: __('Tornevall Tools AI', 'tornevall-tools-for-wordpress'), value: 'tools'},
					{label: __('OpenAI direct', 'tornevall-tools-for-wordpress'), value: 'openai'}
				],
				onChange: function (value) {
					setProvider(value);
					setModel(defaultModelForProvider(value));
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

	function CustomTextControls(props) {
		var state = props.state;
		var accept = (settings.allowedUploads || ['txt', 'md', 'html', 'docx', 'doc', 'pdf']).map(function (ext) { return '.' + ext; }).join(',');
		var uploadName = state.uploadName[0];
		var uploadWarning = state.uploadWarning[0];
		var isUploading = state.isUploading[0];

		return el('div', {className: 'ttfw-ai-custom-text'},
			el(TextareaControl, {
				label: __('Custom text', 'tornevall-tools-for-wordpress'),
				help: __('Paste text here, or upload a document below. Instructions decide how completely the text should be rewritten.', 'tornevall-tools-for-wordpress'),
				rows: 10,
				value: state.customText[0],
				onChange: state.customText[1]
			}),
			el('div', {className: 'ttfw-ai-upload'},
				el('label', {className: 'ttfw-ai-upload__label'}, __('Upload document for cleanup', 'tornevall-tools-for-wordpress')),
				el('input', {type: 'file', accept: accept, disabled: isUploading, onChange: function (event) {
					uploadDocument(event.target.files && event.target.files[0] ? event.target.files[0] : null, state);
					event.target.value = '';
				}}),
				isUploading ? el('span', {className: 'ttfw-ai-upload__status'}, el(Spinner, {}), __('Reading document...', 'tornevall-tools-for-wordpress')) : null,
				uploadName ? el('p', {className: 'ttfw-ai-upload__status'}, __('Loaded: ', 'tornevall-tools-for-wordpress') + uploadName) : null,
				uploadWarning ? el(Notice, {status: 'warning', isDismissible: false}, uploadWarning) : null
			)
		);
	}

	function ResultControls(props) {
		var result = props.result;
		var allowInsert = result && blockEditor;

		function replaceSelected() {
			var dispatch = wp.data.dispatch('core/block-editor');
			var select = wp.data.select('core/block-editor');
			var ids = select.getSelectedBlockClientIds ? select.getSelectedBlockClientIds() : [];
			var blocks = markdownToBlocks(result);
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
			var blocks = markdownToBlocks(result);
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
			el('p', {className: 'ttfw-ai-muted'}, __('Markdown is converted to WordPress-compatible blocks when inserted.', 'tornevall-tools-for-wordpress')),
			el('div', {className: 'ttfw-ai-actions'},
				el(Button, {variant: 'primary', disabled: !allowInsert, onClick: insertAfterSelected}, __('Insert as WP blocks', 'tornevall-tools-for-wordpress')),
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
				label: __('Instructions', 'tornevall-tools-for-wordpress'),
				help: __('Tell AI what to do. Selected blocks and custom text are sent as separate context through the server-side proxy.', 'tornevall-tools-for-wordpress'),
				rows: 6,
				value: state.prompt[0],
				onChange: state.prompt[1]
			}),
			el(CustomTextControls, {state: state}),
			el(TextareaControl, {label: __('Persona override', 'tornevall-tools-for-wordpress'), rows: 5, value: state.persona[0], onChange: state.persona[1]}),
			el('div', {className: 'ttfw-ai-actions'},
				el(Button, {variant: 'primary', disabled: isLoading || state.isUploading[0], onClick: function () { runAi(state); }}, isLoading ? __('Generating...', 'tornevall-tools-for-wordpress') : __('Generate', 'tornevall-tools-for-wordpress')),
				settings.settingsUrl ? el(Button, {variant: 'link', href: settings.settingsUrl, target: '_blank'}, __('Settings', 'tornevall-tools-for-wordpress')) : null
			),
			isLoading ? el('div', {className: 'ttfw-ai-loading'}, el(Spinner, {})) : null,
			error ? el(Notice, {status: 'error', isDismissible: false}, error) : null,
			el(ResultControls, {result: result})
		);
	}

	function getRewriteSourceText(clientId, props) {
		var select = wp.data.select('core/block-editor');
		var block = select.getBlock && clientId ? select.getBlock(clientId) : null;
		var source = blockToText(block);

		if (!source && props) {
			source = blockToText({attributes: props.attributes || {}, innerBlocks: []});
		}

		return limit(source, 50000);
	}

	function toolbarActionPrompt(action) {
		var prompts = {
			rephrase: __('Rewrite the selected block in a clear new wording. Keep the meaning, but do not keep the original sentence structure. Return clean Markdown for WordPress blocks.', 'tornevall-tools-for-wordpress'),
			simplify: __('Simplify the selected block. Keep the important meaning, remove unnecessary complexity, and return clean Markdown for WordPress blocks.', 'tornevall-tools-for-wordpress'),
			summarize: __('Summarize the selected block. Keep only the important points and return clean Markdown for WordPress blocks.', 'tornevall-tools-for-wordpress'),
			expand: __('Expand the selected block with useful detail while staying faithful to the source text. Return clean Markdown for WordPress blocks.', 'tornevall-tools-for-wordpress'),
			shorten: __('Make the selected block shorter and sharper. Keep the core meaning and return clean Markdown for WordPress blocks.', 'tornevall-tools-for-wordpress'),
			clearer: __('Rewrite the selected block so it becomes clearer, more direct, and easier to read. Return clean Markdown for WordPress blocks.', 'tornevall-tools-for-wordpress'),
			formal: __('Rewrite the selected block in a more formal and professional tone. Return clean Markdown for WordPress blocks.', 'tornevall-tools-for-wordpress'),
			casual: __('Rewrite the selected block in a more relaxed and conversational tone. Return clean Markdown for WordPress blocks.', 'tornevall-tools-for-wordpress'),
			swedish: __('Translate the selected block to Swedish. Keep formatting as clean Markdown for WordPress blocks.', 'tornevall-tools-for-wordpress'),
			english: __('Translate the selected block to English. Keep formatting as clean Markdown for WordPress blocks.', 'tornevall-tools-for-wordpress')
		};

		return prompts[action] || prompts.rephrase;
	}

	function toolbarControls(runAction, openCustomPrompt) {
		return [
			{title: __('Ask AI Assistant', 'tornevall-tools-for-wordpress'), icon: 'admin-comments', onClick: openCustomPrompt},
			{title: __('Rephrase', 'tornevall-tools-for-wordpress'), icon: 'update', onClick: function () { runAction('rephrase'); }},
			{title: __('Simplify', 'tornevall-tools-for-wordpress'), icon: 'editor-removeformatting', onClick: function () { runAction('simplify'); }},
			{title: __('Summarize', 'tornevall-tools-for-wordpress'), icon: 'editor-ol', onClick: function () { runAction('summarize'); }},
			{title: __('Expand', 'tornevall-tools-for-wordpress'), icon: 'editor-alignleft', onClick: function () { runAction('expand'); }},
			{title: __('Make shorter', 'tornevall-tools-for-wordpress'), icon: 'editor-contract', onClick: function () { runAction('shorten'); }},
			{title: __('Make clearer', 'tornevall-tools-for-wordpress'), icon: 'visibility', onClick: function () { runAction('clearer'); }},
			{title: __('More formal', 'tornevall-tools-for-wordpress'), icon: 'businessperson', onClick: function () { runAction('formal'); }},
			{title: __('More casual', 'tornevall-tools-for-wordpress'), icon: 'format-chat', onClick: function () { runAction('casual'); }},
			{title: __('Translate to Swedish', 'tornevall-tools-for-wordpress'), icon: 'translation', onClick: function () { runAction('swedish'); }},
			{title: __('Translate to English', 'tornevall-tools-for-wordpress'), icon: 'translation', onClick: function () { runAction('english'); }}
		];
	}

	function BlockRewriteToolbar(props) {
		var blockProps = props.blockProps;
		var isLoadingState = useState(false);
		var errorState = useState('');
		var modalState = useState(false);
		var customPromptState = useState('');
		var isLoading = isLoadingState[0];
		var setIsLoading = isLoadingState[1];
		var error = errorState[0];
		var setError = errorState[1];
		var isModalOpen = modalState[0];
		var setIsModalOpen = modalState[1];
		var customPrompt = customPromptState[0];
		var setCustomPrompt = customPromptState[1];

		function replaceCurrentBlockWithAi(prompt) {
			var source = getRewriteSourceText(blockProps.clientId, blockProps);

			setError('');
			if (!source) {
				setError(__('This block has no readable text to rewrite.', 'tornevall-tools-for-wordpress'));
				return;
			}

			setIsLoading(true);
			runAiRequest({
				provider: settings.defaultProvider || 'tools',
				prompt: prompt,
				customText: source,
				context: 'Inline block toolbar rewrite for block type: ' + blockProps.name,
				persona: settings.defaultPersona || '',
				model: defaultModelForProvider(settings.defaultProvider || 'tools'),
				outputFormat: 'wp_markdown'
			}).then(function (response) {
				var blocks = markdownToBlocks(response && response.text ? response.text : '');
				wp.data.dispatch('core/block-editor').replaceBlocks([blockProps.clientId], blocks);
			}).catch(function (apiError) {
				setError(apiError && apiError.message ? apiError.message : __('The block rewrite failed.', 'tornevall-tools-for-wordpress'));
			}).finally(function () {
				setIsLoading(false);
			});
		}

		function runAction(action) {
			replaceCurrentBlockWithAi(toolbarActionPrompt(action));
		}

		function runCustomPrompt() {
			var prompt = String(customPrompt || '').trim();

			if (!prompt) {
				setError(__('Write a custom instruction first.', 'tornevall-tools-for-wordpress'));
				return;
			}

			setIsModalOpen(false);
			replaceCurrentBlockWithAi(prompt + '\n\n' + __('Return clean Markdown that can be converted to WordPress blocks.', 'tornevall-tools-for-wordpress'));
		}

		if (!blockProps || !blockProps.isSelected || !BlockControls || !ToolbarDropdownMenu) {
			return null;
		}

		return el(Fragment, {},
			el(BlockControls, {group: 'other'},
				el(ToolbarDropdownMenu, {
					icon: isLoading ? 'update' : 'admin-comments',
					label: __('Tornevall AI rewrite', 'tornevall-tools-for-wordpress'),
					text: __('AI', 'tornevall-tools-for-wordpress'),
					controls: toolbarControls(runAction, function () { setIsModalOpen(true); })
				})
			),
			isModalOpen && Modal ? el(Modal, {
				title: __('Ask AI Assistant', 'tornevall-tools-for-wordpress'),
				onRequestClose: function () { setIsModalOpen(false); }
			},
				el(TextareaControl, {
					label: __('Custom rewrite instruction', 'tornevall-tools-for-wordpress'),
					help: __('The selected block text is sent as source text. This field is only the instruction.', 'tornevall-tools-for-wordpress'),
					rows: 6,
					value: customPrompt,
					onChange: setCustomPrompt
				}),
				el('div', {className: 'ttfw-ai-actions'},
					el(Button, {variant: 'primary', disabled: isLoading, onClick: runCustomPrompt}, __('Rewrite block', 'tornevall-tools-for-wordpress')),
					el(Button, {variant: 'secondary', onClick: function () { setIsModalOpen(false); }}, __('Cancel', 'tornevall-tools-for-wordpress'))
				)
			) : null,
			error ? el(Notice, {status: 'error', isDismissible: true, onRemove: function () { setError(''); }}, error) : null
		);
	}

	function withAiRewriteToolbar(BlockEdit) {
		return function (props) {
			return el(Fragment, {},
				el(BlockEdit, props),
				el(BlockRewriteToolbar, {blockProps: props})
			);
		};
	}

	function SidebarRender() {
		var state = useAiState(__('Rewrite the selected block or custom text into clean WordPress-compatible Markdown.', 'tornevall-tools-for-wordpress'));
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
		var state = useAiState(__('Rewrite the custom text into clean WordPress-compatible Markdown.', 'tornevall-tools-for-wordpress'));
		var result = state.result[0];
		return el('div', {className: 'ttfw-ai-block'},
			el(Placeholder, {
				icon: 'admin-comments',
				label: __('Tornevall AI Assistant', 'tornevall-tools-for-wordpress'),
				instructions: __('Generate, rewrite, clean up, or convert text with Tornevall Tools AI or direct OpenAI. Upload documents or paste custom text, then insert the result as WordPress-compatible blocks.', 'tornevall-tools-for-wordpress')
			},
				el(AssistantPanel, {state: state}),
				result ? el(Button, {variant: 'secondary', onClick: function () { props.setAttributes({content: markdownToHtml(result)}); }}, __('Store result in this block', 'tornevall-tools-for-wordpress')) : null
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

	if (addFilter && BlockControls && ToolbarDropdownMenu) {
		addFilter('editor.BlockEdit', 'tornevall-tools/ai-rewrite-toolbar', withAiRewriteToolbar);
	}

	registerBlockType('tornevall-tools/ai-assistant', {
		title: __('Tornevall AI Assistant', 'tornevall-tools-for-wordpress'),
		description: __('Generate, rewrite, clean up, and convert editor text with Tornevall Tools AI or direct OpenAI.', 'tornevall-tools-for-wordpress'),
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
