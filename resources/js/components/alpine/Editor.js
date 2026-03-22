import { EditorState, Plugin, PluginKey, TextSelection } from 'prosemirror-state';
import { EditorView } from 'prosemirror-view';
import { Schema, DOMParser, DOMSerializer, Fragment, Slice } from 'prosemirror-model';
import { schema as basicSchema } from 'prosemirror-schema-basic';
import { addListNodes, wrapInList, splitListItem, liftListItem } from 'prosemirror-schema-list';
import { toggleMark, wrapIn, baseKeymap, chainCommands, lift, liftEmptyBlock } from 'prosemirror-commands';
import { history, undo, redo } from 'prosemirror-history';
import { keymap } from 'prosemirror-keymap';

const strikethroughMark = {
    parseDOM: [{ tag: 's' }, { tag: 'del' }, { style: 'text-decoration=line-through' }],
    toDOM() {
        return ['s', 0];
    },
};

const linkMark = {
    attrs: {
        href: {},
        isEmbed: { default: false },
        isMention: { default: false },
        isRawUrl: { default: false },
        rel: { default: 'nofollow noopener' },
        target: { default: '_blank' },
        title: { default: null },
    },
    inclusive: false,
    parseDOM: [
        {
            tag: 'a[href]',
            getAttrs(dom) {
                return {
                    href: dom.getAttribute('href'),
                    isEmbed: dom.getAttribute('data-embed') === 'true',
                    isMention: dom.getAttribute('data-mention') === 'true',
                    isRawUrl: dom.getAttribute('data-raw-url') === 'true',
                    rel: dom.getAttribute('rel'),
                    target: dom.getAttribute('target'),
                    title: dom.getAttribute('title'),
                };
            },
        },
    ],
    toDOM(node) {
        const { isEmbed, isMention, isRawUrl, ...rest } = node.attrs;
        const attrs = {
            ...rest,
            rel: 'nofollow noopener',
            target: '_blank',
        };

        if (isEmbed) {
            attrs['data-embed'] = 'true';
        }

        if (isMention) {
            attrs['data-mention'] = 'true';
        }

        if (isRawUrl) {
            attrs['data-raw-url'] = 'true';
        }

        return ['a', attrs, 0];
    },
};

const editorSchema = new Schema({
    marks: basicSchema.spec.marks.update('link', linkMark).addToEnd('strikethrough', strikethroughMark),
    nodes: addListNodes(basicSchema.spec.nodes, 'paragraph block*', 'block'),
});

function getMention(state) {
    const $cursor = state.selection.$from;

    // 1. Only work if cursor is in a text node
    if (!$cursor.parent.isTextblock) return null;

    // 2. NEW: Check if cursor is already inside a Link.
    // If we are sitting in an existing link (like an existing mention), stops here.
    const hasLink = ($cursor.marks() || []).some((m) => m.type.name === 'link');
    if (hasLink) return null;

    // 3. Look at the text before the cursor
    const scanLimit = 20;

    // (We removed the unsafe .child() check here to fix the RangeError)

    const textBefore = $cursor.parent.textBetween(
        Math.max(0, $cursor.parentOffset - scanLimit),
        $cursor.parentOffset,
        null,
        '\ufffc'
    );

    // 4. Regex to find '@' followed by word characters at the end of the string
    const match = textBefore.match(/@([a-zA-Z0-9_]*)$/);

    if (!match) return null;

    // 5. Calculate exact positions in the document
    const query = match[1];
    const start = $cursor.pos - match[0].length;
    const end = $cursor.pos;

    return { range: { from: start, to: end }, query };
}

// Mock User Data (Replace with your API fetch)
const allUsers = [
    { username: 'albert', name: 'Albert Einstein' },
    { username: 'marie', name: 'Marie Curie' },
    { username: 'isaac', name: 'Isaac Newton' },
    { username: 'galileo', name: 'Galileo Galilei' },
    { username: 'nikola', name: 'Nikola Tesla' },
    { username: 'ada', name: 'Ada Lovelace' },
    { username: 'charles', name: 'Charles Darwin' },
];

export default (content = '') => {
    let editorView = null;

    return {
        content,
        isUploading: false,
        updatedAt: Date.now(),

        // Mentions
        users: [],
        mentionCoords: { top: 0, left: 0 }, // For positioning the menu
        mentionIndex: 0, // For keyboard navigation
        mentionQuery: '',
        mentionRange: null, // Stores {from, to} to replace
        showMentionMenu: false,

        init() {
            const insertHardBreak = (state, dispatch) => {
                const br = editorSchema.nodes.hard_break;

                if (dispatch) {
                    dispatch(state.tr.replaceSelectionWith(br.create()).scrollIntoView());
                }

                return true;
            };

            const listKeymap = keymap({
                Enter: chainCommands(liftEmptyBlock, splitListItem(editorSchema.nodes.list_item)),
                'Shift-Tab': liftListItem(editorSchema.nodes.list_item),
            });

            const mentionPlugin = new Plugin({
                key: new PluginKey('mention'),

                // 1. View: updates Alpine state on every keystroke
                view: () => {
                    return {
                        update: async (view) => {
                            const match = getMention(view.state);

                            if (match && match.query.length > 0) {
                                // Get screen coordinates for the menu
                                const start = view.coordsAtPos(match.range.from);
                                const box = this.$refs.editorWrapper.getBoundingClientRect();

                                // Update Alpine state
                                this.mentionQuery = match.query;
                                this.mentionRange = match.range;
                                this.showMentionMenu = true;

                                // Fetch matching usernames
                                const res = await axios.post('/api/users/search', {
                                    q: match.query,
                                    limit: 10,
                                });
                                this.users = res.data;

                                // Reset index if list changes
                                if (this.mentionIndex >= this.users.length) {
                                    this.mentionIndex = 0;
                                }

                                // Position the menu relative to the editor wrapper
                                this.mentionCoords = {
                                    top: start.bottom - box.top + 'px',
                                    left: start.left - box.left + 'px',
                                };
                            } else {
                                // Hide if no match
                                this.mentionIndex = 0;
                                this.showMentionMenu = false;
                            }
                        },
                    };
                },

                // 2. Props: Intercept Keydown for Navigation
                props: {
                    handleKeyDown: (view, event) => {
                        if (!this.showMentionMenu || this.users.length === 0) {
                            return false;
                        }

                        if (event.key === 'ArrowUp') {
                            this.mentionIndex = this.mentionIndex > 0 ? this.mentionIndex - 1 : this.users.length - 1;
                            this.scrollToMentionItem();
                            return true;
                        }

                        if (event.key === 'ArrowDown') {
                            this.mentionIndex = this.mentionIndex < this.users.length - 1 ? this.mentionIndex + 1 : 0;
                            this.scrollToMentionItem();
                            return true;
                        }

                        if (event.key === 'Enter') {
                            this.selectUser(this.users[this.mentionIndex]);
                            return true;
                        }

                        if (event.key === 'Escape') {
                            this.showMentionMenu = false;
                            return true;
                        }

                        return false;
                    },
                },
            });

            const plugins = [
                history(),
                mentionPlugin,
                listKeymap,
                keymap({
                    'Mod-b': toggleMark(editorSchema.marks.strong),
                    'Mod-i': toggleMark(editorSchema.marks.em),
                    'Mod-k': this.toggleLink.bind(this),
                    'Mod-z': undo,
                    'Mod-y': redo,
                    'Mod-Shift-x': toggleMark(editorSchema.marks.strikethrough),
                    'Shift-Enter': insertHardBreak,
                }),
                keymap(baseKeymap),
            ];

            // Create content element to edit
            const $content = document.createElement('div');
            $content.innerHTML = this.content;
            const doc = DOMParser.fromSchema(editorSchema).parse($content);

            // Create state
            const state = EditorState.create({ doc, plugins });

            // Create editor view
            const $editor = this.$el.querySelector('.js-editorView');

            if ($editor) {
                editorView = new EditorView($editor, {
                    handlePaste: this.onPaste,
                    state,
                    dispatchTransaction: (transaction) => {
                        const newState = editorView.state.apply(transaction);
                        editorView.updateState(newState);

                        if (transaction.docChanged) {
                            this.content = this.getHTML();
                        }

                        this.updatedAt = Date.now();
                    },
                });
            }

            // Upload handler
            if (this.$refs.imageInput) {
                this.$refs.imageInput.addEventListener('change', this.onImageUpload.bind(this));
            }

            // Watch for external content updates (e.g. Livewire setting body via replyToPost)
            this.$watch('content', (newContent) => {
                if (!editorView) return;

                // Skip if the editor itself caused this change
                if (newContent === this.getHTML()) return;

                const $content = document.createElement('div');
                $content.innerHTML = newContent || '';
                const doc = DOMParser.fromSchema(editorSchema).parse($content);
                const newState = EditorState.create({ doc, plugins: editorView.state.plugins });
                editorView.updateState(newState);

                // Insert a trailing space and place the cursor after it
                const { state } = editorView;
                const end = state.doc.content.size - 1;
                const tr = state.tr.insertText(' ', end);
                tr.setSelection(TextSelection.create(tr.doc, end + 1));
                editorView.dispatch(tr.scrollIntoView());
                editorView.focus();
            });
        },

        execute(command) {
            if (!editorView) {
                return;
            }

            editorView.focus();
            command(editorView.state, editorView.dispatch, editorView);
        },

        scrollToMentionItem() {
            this.$nextTick(() => {
                const $activeItem = document.querySelector('.editor__mention-item.is-active');
                if ($activeItem) {
                    $activeItem.scrollIntoView({ block: 'nearest' });
                }
            });
        },

        selectUser(user) {
            if (!editorView || !this.mentionRange) return;

            const { from, to } = this.mentionRange;
            const schema = editorView.state.schema;

            // 1. Create the Link Mark with isMention: true
            const linkMark = schema.marks.link.create({
                href: `/@${user.username}`,
                isMention: true,
                title: user.name,
            });

            // 2. Create Text Node ("@username") with the Link Mark
            const textNode = schema.text(`@${user.username}`, [linkMark]);

            // 3. Insert space after
            const spaceNode = schema.text(' ');

            // 4. Perform Transaction
            const tr = editorView.state.tr.replaceWith(from, to, [textNode, spaceNode]);

            editorView.dispatch(tr);
            editorView.focus();

            // 5. Close Menu
            this.showMentionMenu = false;
        },

        getHTML() {
            if (!editorView) {
                return '';
            }

            const serializer = DOMSerializer.fromSchema(editorSchema);
            const fragment = serializer.serializeFragment(editorView.state.doc.content);
            const $div = document.createElement('div');
            $div.appendChild(fragment);
            return $div.innerHTML;
        },

        isActive(type, attrs = {}) {
            if (!editorView) return false;

            const _ = this.updatedAt;
            const state = editorView.state;
            const { from, $from, to, empty } = state.selection;

            if (['strong', 'em', 'code', 'link', 'strikethrough'].includes(type)) {
                const markType = editorSchema.marks[type];
                if (!markType) return false;

                if (empty) {
                    return markType.isInSet(state.storedMarks || $from.marks());
                }
                return state.doc.rangeHasMark(from, to, markType);
            }

            if (['bullet_list', 'ordered_list', 'blockquote'].includes(type)) {
                const nodeType = editorSchema.nodes[type];

                for (let d = $from.depth; d > 0; d--) {
                    if ($from.node(d).type === nodeType) return true;
                }

                return false;
            }

            return false;
        },

        async onImageUpload(event) {
            if (!event.target.files[0]) {
                return;
            }

            const formData = new FormData();
            formData.append('image', event.target.files[0]);

            this.isUploading = true;
            const $progressBar = this.$refs.progressBar;

            try {
                const url = 'https://api.imgbb.com/1/upload?key=fb4e07fb564cec578f279e18fe1ab21d';
                const res = await axios.post(url, formData, {
                    onUploadProgress: function (progressEvent) {
                        $progressBar.style.setProperty('--progress', +(progressEvent.loaded / progressEvent.total));
                    },
                });

                if (res.data.success) {
                    const imageUrl = res.data.data.image.url;

                    if (editorView) {
                        const state = editorView.state;
                        const schema = state.schema;
                        const linkMark = schema.marks.link.create({
                            href: imageUrl,
                            isEmbed: true,
                            isRawUrl: true,
                        });
                        const textNode = schema.text(imageUrl, [linkMark]);
                        const paragraphNode = schema.nodes.paragraph.create(null, textNode);

                        editorView.dispatch(state.tr.replaceSelectionWith(paragraphNode).scrollIntoView());
                        editorView.focus();
                    }
                } else {
                    console.error(res.data);
                }
            } catch (error) {
                console.error(error);
            }

            // Reset button and input
            this.$refs.imageInput.value = null;
            this.$refs.progressBar.style.setProperty('--progress', 0);
            this.isUploading = false;
        },

        onPaste(view, event, slice) {
            const text = event.clipboardData.getData('text/plain').trim();

            if (!text) {
                return false;
            }

            const isUrl = /^(https?:\/\/|www\.)[^\s]+$/.test(text);
            const schema = view.state.schema;
            const lines = text.split(/\r\n|\r|\n/);

            const nodes = lines.map((line) => {
                if (!line) {
                    return schema.nodes.paragraph.create();
                }

                const parts = line.split(/((?:https?:\/\/|www\.)[^\s]+)/g);
                const inlineContent = parts
                    .map((part) => {
                        if (!part) {
                            return null;
                        }

                        if (part.match(/^(https?:\/\/|www\.)/)) {
                            let href = part;

                            if (!href.match(/^https?:\/\//)) {
                                href = 'https://' + href;
                            }

                            const attrs = {
                                href,
                                isEmbed: isUrl && part === text,
                                isRawUrl: true,
                            };

                            return schema.text(part, [schema.marks.link.create(attrs)]);
                        }

                        return schema.text(part);
                    })
                    .filter(Boolean);

                return schema.nodes.paragraph.create(null, inlineContent);
            });

            const fragment = Fragment.from(nodes);
            const newSlice = new Slice(fragment, 1, 1);

            view.dispatch(view.state.tr.replaceSelection(newSlice));

            return true;
        },

        toggleBlockquote() {
            if (this.isActive('blockquote')) {
                this.execute(lift);
            } else {
                this.execute(wrapIn(editorSchema.nodes.blockquote));
            }
        },

        toggleBold() {
            this.execute(toggleMark(editorSchema.marks.strong));
        },

        toggleItalic() {
            this.execute(toggleMark(editorSchema.marks.em));
        },

        toggleLink() {
            const previousUrl = this.isActive('link') ? '...' : '';
            const url = window.prompt('Enter URL', previousUrl);

            if (url === null) {
                return;
            }

            if (url === '') {
                this.execute(toggleMark(editorSchema.marks.link, null));
                return;
            }

            this.execute(toggleMark(editorSchema.marks.link, { href: url }));
        },

        toggleList(targetListType) {
            this.execute((state, dispatch) => {
                const { $from } = state.selection;
                let parentList = null;
                let parentPos = null;

                for (let d = $from.depth; d > 0; d--) {
                    const node = $from.node(d);
                    if (node.type === editorSchema.nodes.bullet_list || node.type === editorSchema.nodes.ordered_list) {
                        parentList = node;
                        parentPos = $from.before(d);
                        break;
                    }
                }

                if (!parentList) {
                    return wrapInList(targetListType)(state, dispatch);
                }

                if (parentList.type === targetListType) {
                    return liftListItem(editorSchema.nodes.list_item)(state, dispatch);
                } else {
                    if (dispatch) {
                        dispatch(state.tr.setNodeMarkup(parentPos, targetListType));
                    }

                    return true;
                }
            });
        },

        toggleOrderedList() {
            this.toggleList(editorSchema.nodes.ordered_list);
        },

        toggleStrikethrough() {
            this.execute(toggleMark(editorSchema.marks.strikethrough));
        },

        toggleUnorderedList() {
            this.toggleList(editorSchema.nodes.bullet_list);
        },

        unlink() {
            this.execute((state, dispatch) => {
                const markType = editorSchema.marks.link;
                const { selection, doc } = state;
                let { from, to } = selection;

                if (selection.empty) {
                    const { $from } = selection;

                    if (!markType.isInSet(state.storedMarks || $from.marks())) {
                        return false;
                    }

                    while (from > 0 && doc.rangeHasMark(from - 1, from, markType)) {
                        from--;
                    }

                    while (to < doc.content.size && doc.rangeHasMark(to, to + 1, markType)) {
                        to++;
                    }
                }

                if (dispatch) {
                    dispatch(state.tr.removeMark(from, to, markType));
                }

                return true;
            });
        },
    };
};
