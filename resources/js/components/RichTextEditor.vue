<script setup lang="ts">
import { Ckeditor } from '@ckeditor/ckeditor5-vue';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { computed } from 'vue';

/**
 * Shared rich-text editor wrapper around CKEditor 5 (classic build).
 *
 * The whole project standardises on CKEditor; this component is the single
 * place the editor is wired so it can be lazy-loaded (via defineAsyncComponent)
 * at the call sites and kept out of the main bundle. Outputs HTML via v-model.
 */
const props = withDefaults(
    defineProps<{
        modelValue?: string;
        disabled?: boolean;
        placeholder?: string;
        config?: Record<string, unknown>;
    }>(),
    {
        modelValue: '',
        disabled: false,
        placeholder: '',
    },
);

defineEmits<{ 'update:modelValue': [value: string] }>();

const editor = ClassicEditor;

const editorConfig = computed(() => ({
    toolbar: ['bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo'],
    ...(props.placeholder ? { placeholder: props.placeholder } : {}),
    ...(props.config ?? {}),
}));
</script>

<template>
    <Ckeditor
        :editor="editor"
        :model-value="modelValue"
        :config="editorConfig"
        :disabled="disabled"
        @update:model-value="(val: string) => $emit('update:modelValue', val)"
    />
</template>
