<script setup lang="ts">
/**
 * Shared rich-text editor (TipTap 3 — MIT licensed, no licence key needed).
 *
 * Every rich-text field in the app goes through this component, lazy-loaded via
 * defineAsyncComponent at the call sites so the editor stays out of the main
 * bundle. v-model is HTML, the same format CKEditor produced, so existing
 * saved content loads unchanged.
 */
import { Placeholder } from '@tiptap/extensions';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { Bold, Heading2, Italic, Link2, List, ListOrdered, Quote, Redo2, Strikethrough, Underline, Undo2 } from 'lucide-vue-next';
import { onBeforeUnmount, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        disabled?: boolean;
        placeholder?: string;
        /** Kept for backwards compatibility with the old CKEditor wrapper; ignored. */
        config?: Record<string, unknown>;
    }>(),
    { modelValue: '', disabled: false, placeholder: '' },
);

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const editor = useEditor({
    content: props.modelValue ?? '',
    editable: !props.disabled,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
            link: { openOnClick: false, autolink: true, defaultProtocol: 'https' },
        }),
        Placeholder.configure({ placeholder: props.placeholder || 'Write here…' }),
    ],
    // An empty editor is "<p></p>"; store it as an empty string instead.
    onUpdate: ({ editor }) => emit('update:modelValue', editor.isEmpty ? '' : editor.getHTML()),
});

// Keep the editor in sync when the value changes from outside (e.g. edit form
// filled after load, form reset). Skip when it's our own echo.
watch(
    () => props.modelValue,
    (value) => {
        const ed = editor.value;
        if (!ed) return;
        const next = value ?? '';
        const current = ed.isEmpty ? '' : ed.getHTML();
        if (next !== current) ed.commands.setContent(next, { emitUpdate: false });
    },
);

watch(
    () => props.disabled,
    (disabled) => editor.value?.setEditable(!disabled),
);

onBeforeUnmount(() => editor.value?.destroy());

const setLink = () => {
    const ed = editor.value;
    if (!ed) return;
    const previous = ed.getAttributes('link').href ?? '';
    const url = window.prompt('Link URL', previous);
    if (url === null) return;
    if (url.trim() === '') {
        ed.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }
    ed.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
};

type Tool = { icon: any; label: string; run: () => void; active?: () => boolean; disabled?: () => boolean };
const tools: (Tool | 'sep')[] = [
    { icon: Bold, label: 'Bold', run: () => editor.value?.chain().focus().toggleBold().run(), active: () => !!editor.value?.isActive('bold') },
    { icon: Italic, label: 'Italic', run: () => editor.value?.chain().focus().toggleItalic().run(), active: () => !!editor.value?.isActive('italic') },
    { icon: Underline, label: 'Underline', run: () => editor.value?.chain().focus().toggleUnderline().run(), active: () => !!editor.value?.isActive('underline') },
    { icon: Strikethrough, label: 'Strikethrough', run: () => editor.value?.chain().focus().toggleStrike().run(), active: () => !!editor.value?.isActive('strike') },
    'sep',
    { icon: Heading2, label: 'Heading', run: () => editor.value?.chain().focus().toggleHeading({ level: 2 }).run(), active: () => !!editor.value?.isActive('heading', { level: 2 }) },
    { icon: List, label: 'Bulleted list', run: () => editor.value?.chain().focus().toggleBulletList().run(), active: () => !!editor.value?.isActive('bulletList') },
    { icon: ListOrdered, label: 'Numbered list', run: () => editor.value?.chain().focus().toggleOrderedList().run(), active: () => !!editor.value?.isActive('orderedList') },
    { icon: Quote, label: 'Quote', run: () => editor.value?.chain().focus().toggleBlockquote().run(), active: () => !!editor.value?.isActive('blockquote') },
    { icon: Link2, label: 'Link', run: setLink, active: () => !!editor.value?.isActive('link') },
    'sep',
    { icon: Undo2, label: 'Undo', run: () => editor.value?.chain().focus().undo().run(), disabled: () => !editor.value?.can().undo() },
    { icon: Redo2, label: 'Redo', run: () => editor.value?.chain().focus().redo().run(), disabled: () => !editor.value?.can().redo() },
];
</script>

<template>
    <div class="rte overflow-hidden rounded-md border border-gray-200 bg-white focus-within:border-primary/60" :class="{ 'opacity-60': disabled }">
        <div v-if="editor && !disabled" class="flex flex-wrap items-center gap-0.5 border-b border-gray-200 bg-gray-50 px-1.5 py-1">
            <template v-for="(tool, i) in tools" :key="i">
                <span v-if="tool === 'sep'" class="mx-1 h-5 w-px bg-gray-200" />
                <button
                    v-else
                    type="button"
                    :title="tool.label"
                    :aria-label="tool.label"
                    :aria-pressed="tool.active?.() ?? undefined"
                    :disabled="tool.disabled?.()"
                    class="rounded p-1.5 text-gray-600 transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-40"
                    :class="{ 'bg-primary/10 text-primary': tool.active?.() }"
                    @mousedown.prevent
                    @click="tool.run()"
                >
                    <component :is="tool.icon" class="h-4 w-4" />
                </button>
            </template>
        </div>
        <EditorContent :editor="editor" class="rte-content" />
    </div>
</template>

<style scoped>
.rte-content :deep(.tiptap) {
    min-height: 160px;
    padding: 0.75rem;
    outline: none;
    font-size: 0.875rem;
    line-height: 1.6;
}
.rte-content :deep(.tiptap p) {
    margin: 0 0 0.5rem;
}
.rte-content :deep(.tiptap h2) {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0.75rem 0 0.5rem;
}
.rte-content :deep(.tiptap h3) {
    font-size: 1rem;
    font-weight: 600;
    margin: 0.75rem 0 0.5rem;
}
.rte-content :deep(.tiptap ul) {
    list-style: disc;
    padding-left: 1.5rem;
    margin: 0 0 0.5rem;
}
.rte-content :deep(.tiptap ol) {
    list-style: decimal;
    padding-left: 1.5rem;
    margin: 0 0 0.5rem;
}
.rte-content :deep(.tiptap blockquote) {
    border-left: 3px solid #e5e7eb;
    padding-left: 0.75rem;
    color: #4b5563;
    margin: 0 0 0.5rem;
}
.rte-content :deep(.tiptap a) {
    color: var(--color-primary, #6c3baa);
    text-decoration: underline;
}
.rte-content :deep(.tiptap p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    color: #9ca3af;
    float: left;
    height: 0;
    pointer-events: none;
}
</style>
