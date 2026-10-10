/**
 * The in-place editor of a Handbook document, in the spirit of Outline: the page *is* the
 * editor. TipTap (ProseMirror) with markdown in and out, so the database keeps plain markdown:
 * typing "## " makes a heading, "- [ ] " a checklist, "/" opens the block menu, a selection
 * shows the formatting bubble, a phone gets a toolbar over the keyboard. Changes are saved by
 * the Livewire component after a pause (saveContent), with the version the page was loaded at,
 * so two people editing the same document never overwrite each other silently.
 *
 * Built into dist/editor.js (npm run build) and served by the module: no build step in the app.
 */
import { Editor, Extension } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import Link from '@tiptap/extension-link'
import TaskList from '@tiptap/extension-task-list'
import TaskItem from '@tiptap/extension-task-item'
import Table from '@tiptap/extension-table'
import TableRow from '@tiptap/extension-table-row'
import TableCell from '@tiptap/extension-table-cell'
import TableHeader from '@tiptap/extension-table-header'
import BubbleMenu from '@tiptap/extension-bubble-menu'
import Suggestion from '@tiptap/suggestion'
import { Markdown } from 'tiptap-markdown'

const blocks = [
    { id: 'h1', icon: 'heading', run: c => c.setNode('heading', { level: 1 }) },
    { id: 'h2', icon: 'heading', run: c => c.setNode('heading', { level: 2 }) },
    { id: 'h3', icon: 'heading', run: c => c.setNode('heading', { level: 3 }) },
    { id: 'bullet', icon: 'list-ul', run: c => c.toggleBulletList() },
    { id: 'ordered', icon: 'list-ol', run: c => c.toggleOrderedList() },
    { id: 'todo', icon: 'list-check', run: c => c.toggleTaskList() },
    { id: 'quote', icon: 'quote-left', run: c => c.toggleBlockquote() },
    { id: 'code', icon: 'code', run: c => c.toggleCodeBlock() },
    { id: 'table', icon: 'table', run: c => c.insertTable({ rows: 3, cols: 3, withHeaderRow: true }) },
    { id: 'divider', icon: 'minus', run: c => c.setHorizontalRule() },
]

function el(tag, attrs = {}, children = []) {
    const node = document.createElement(tag)
    Object.entries(attrs).forEach(([k, v]) => (k === 'text' ? (node.textContent = v) : node.setAttribute(k, v)))
    children.forEach(c => node.appendChild(c))
    return node
}

/** The "/" menu: a Bootstrap dropdown under the cursor, filtered by what follows the slash. */
function slashMenu(labels) {
    return Extension.create({
        name: 'slashMenu',
        addProseMirrorPlugins() {
            return [Suggestion({
                editor: this.editor,
                char: '/',
                startOfLine: false,
                allowSpaces: false,
                items: ({ query }) => blocks
                    .map(b => ({ ...b, label: labels.blocks[b.id] || b.id }))
                    .filter(b => b.label.toLowerCase().includes(query.toLowerCase()) || b.id.startsWith(query.toLowerCase())),
                command: ({ editor, range, props }) => props.run(editor.chain().focus().deleteRange(range)).run(),
                render: () => {
                    let menu, items = [], selected = 0, command
                    const draw = () => {
                        menu.replaceChildren(...(items.length ? items.map((item, i) => {
                            const b = el('button', { type: 'button', class: 'dropdown-item d-flex align-items-center gap-2' + (i === selected ? ' active' : '') },
                                [el('i', { class: `fas fa-fw fa-${item.icon}` }), el('span', { text: item.label })])
                            b.addEventListener('mousedown', e => { e.preventDefault(); command(item) })
                            return b
                        }) : [el('span', { class: 'dropdown-item-text text-secondary small', text: labels.noResults })]))
                    }
                    const place = rect => {
                        const r = rect && rect()
                        if (!r) return
                        menu.style.left = Math.min(r.left + window.scrollX, window.scrollX + document.documentElement.clientWidth - 230) + 'px'
                        menu.style.top = (r.bottom + window.scrollY + 4) + 'px'
                    }
                    return {
                        onStart: props => {
                            menu = el('div', { class: 'dropdown-menu show hb-slash-menu shadow' })
                            document.body.appendChild(menu)
                            items = props.items; selected = 0; command = props.command
                            draw(); place(props.clientRect)
                        },
                        onUpdate: props => { items = props.items; selected = 0; command = props.command; draw(); place(props.clientRect) },
                        onKeyDown: ({ event }) => {
                            if (!items.length) return false
                            if (event.key === 'ArrowDown') { selected = (selected + 1) % items.length; draw(); return true }
                            if (event.key === 'ArrowUp') { selected = (selected + items.length - 1) % items.length; draw(); return true }
                            if (event.key === 'Enter') { command(items[selected]); return true }
                            if (event.key === 'Escape') { menu.remove(); return true }
                            return false
                        },
                        onExit: () => menu && menu.remove(),
                    }
                },
            })]
        },
    })
}

/** The formatting bubble shown on a selection. */
function bubble(labels, run) {
    const buttons = [
        ['bold', 'bold'], ['italic', 'italic'], ['strike', 'strikethrough'], ['code', 'code'], ['link', 'link'],
        ['h2', 'heading'], ['quote', 'quote-left'],
    ]
    const menu = el('div', { class: 'btn-group btn-group-sm shadow hb-bubble bg-body' }, buttons.map(([cmd, icon]) => {
        const b = el('button', { type: 'button', class: 'btn btn-outline-secondary', title: labels.commands[cmd] || cmd, 'data-cmd': cmd },
            [el('i', { class: `fas fa-fw fa-${icon}` })])
        b.addEventListener('mousedown', e => { e.preventDefault(); run(cmd) })
        return b
    }))
    return menu
}

function command(editor, cmd, labels) {
    const c = editor.chain().focus()
    switch (cmd) {
        case 'bold': return c.toggleBold().run()
        case 'italic': return c.toggleItalic().run()
        case 'strike': return c.toggleStrike().run()
        case 'code': return c.toggleCode().run()
        case 'h2': return c.toggleHeading({ level: 2 }).run()
        case 'h3': return c.toggleHeading({ level: 3 }).run()
        case 'bullet': return c.toggleBulletList().run()
        case 'todo': return c.toggleTaskList().run()
        case 'quote': return c.toggleBlockquote().run()
        case 'codeblock': return c.toggleCodeBlock().run()
        case 'undo': return c.undo().run()
        case 'redo': return c.redo().run()
        case 'link': {
            const previous = editor.getAttributes('link').href || 'https://'
            const url = window.prompt(labels.linkPrompt, previous)
            if (url === null) return
            return url.trim() === '' ? c.unsetLink().run() : c.extendMarkRange('link').setLink({ href: url.trim() }).run()
        }
    }
}

/**
 * Alpine component: x-data="handbookEditor({...})" on the document page.
 * config: markdown, title, version, labels, focusTitle
 */
function handbookEditor(config) {
    let editor = null           // outside Alpine's reactivity: a proxied TipTap editor misbehaves
    let timer = null

    return {
        title: config.title || '',
        status: 'saved',        // saved | unsaved | saving | conflict | error
        version: config.version,
        saving: false,
        again: false,

        init() {
            const labels = config.labels
            const menu = bubble(labels, cmd => command(editor, cmd, labels))
            editor = new Editor({
                element: this.$refs.editor,
                extensions: [
                    StarterKit.configure({ heading: { levels: [1, 2, 3] } }),
                    Placeholder.configure({ placeholder: labels.placeholder }),
                    Link.configure({ openOnClick: false, autolink: true, protocols: ['http', 'https', 'mailto'] }),
                    TaskList, TaskItem.configure({ nested: true }),
                    Table.configure({ resizable: false }), TableRow, TableHeader, TableCell,
                    BubbleMenu.configure({ element: menu, tippyOptions: { duration: 100, placement: 'top' } }),
                    Markdown.configure({ html: false, tightLists: true, linkify: true, breaks: false, transformPastedText: true }),
                    slashMenu(labels),
                ],
                content: config.markdown || '',
                editorProps: { attributes: { class: 'hb-markdown hb-prose', spellcheck: 'true' } },
                onUpdate: () => this.changed(),
            })
            window.addEventListener('beforeunload', e => {
                if (this.status === 'unsaved' || this.status === 'saving') { e.preventDefault(); e.returnValue = '' }
            })
            if (config.focusTitle) this.$nextTick(() => this.$refs.title.focus())
        },

        destroy() { editor && editor.destroy() },

        run(cmd) { command(editor, cmd, config.labels) },

        /** Enter in the title goes to the text, like Outline: at once, so fast typing lands in the text. */
        toText() {
            editor.commands.setTextSelection(1)
            editor.view.focus()
        },

        /** The title is a textarea that grows with its text: a long title wraps on a phone. */
        fit(area) {
            area.style.height = 'auto'
            area.style.height = area.scrollHeight + 'px'
        },

        changed() {
            this.status = 'unsaved'
            clearTimeout(timer)
            timer = setTimeout(() => this.save(), 800)
        },

        async save() {
            if (this.saving) { this.again = true; return }
            clearTimeout(timer)
            this.saving = true
            this.status = 'saving'
            try {
                const result = await this.$wire.saveContent(this.title, editor.storage.markdown.getMarkdown(), this.version)
                if (result.conflict) {
                    this.status = 'conflict'
                    this.again = false
                    return
                }
                this.version = result.version
                this.status = this.again ? 'unsaved' : 'saved'
                document.querySelectorAll('[data-hb-title="' + result.id + '"]').forEach(n => (n.textContent = result.label))
            } catch (e) {
                this.status = 'error'
            } finally {
                this.saving = false
                if (this.again && this.status !== 'conflict') { this.again = false; this.save() }
            }
        },
    }
}

document.addEventListener('alpine:init', () => window.Alpine.data('handbookEditor', handbookEditor))
