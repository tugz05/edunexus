<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue';

interface Props {
    text: string;
    typingSpeed?: number; // milliseconds per character
    showTyping?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    typingSpeed: 15,
    showTyping: true,
});

const displayedText = ref('');
const isTyping = ref(false);
const emit = defineEmits<{
    typingComplete: [];
}>();

// Enhanced markdown-like formatting parser
const formatText = (text: string): string => {
    if (!text) return '';

    let formatted = text;

    // First, protect code blocks and inline code
    const codeBlocks: string[] = [];
    formatted = formatted.replace(/```[\s\S]*?```/g, (match) => {
        codeBlocks.push(match);
        return `__CODEBLOCK_${codeBlocks.length - 1}__`;
    });

    const inlineCodes: string[] = [];
    formatted = formatted.replace(/`([^`]+)`/g, (match, code) => {
        inlineCodes.push(code);
        return `__INLINECODE_${inlineCodes.length - 1}__`;
    });

    // Convert numbered lists (1. item, 2. item, etc.) - more flexible pattern
    formatted = formatted.replace(/^(\d+)\.\s+(.+)$/gm, '<li class="ml-6 list-decimal">$2</li>');
    
    // Convert bullet points (- item, * item)
    formatted = formatted.replace(/^[-*]\s+(.+)$/gm, '<li class="ml-6 list-disc">$1</li>');
    
    // Wrap consecutive list items in <ul> (improved regex)
    formatted = formatted.replace(/(<li[^>]*>.*?<\/li>(?:\s*<li[^>]*>.*?<\/li>)*)/gs, '<ul class="my-2 space-y-1.5 ml-4">$1</ul>');
    
    // Convert **bold** to <strong>
    formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold">$1</strong>');
    
    // Convert *italic* to <em> (but not if it's part of a list marker)
    formatted = formatted.replace(/(?<!^[-*]\s)\*([^*]+?)\*(?!\*)/g, '<em class="italic">$1</em>');
    
    // Restore inline code
    inlineCodes.forEach((code, index) => {
        formatted = formatted.replace(
            `__INLINECODE_${index}__`,
            `<code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-sm font-mono text-gray-800 dark:text-gray-200">${code}</code>`
        );
    });

    // Restore code blocks
    codeBlocks.forEach((block, index) => {
        const code = block.replace(/```[\w]*\n?/g, '').trim();
        formatted = formatted.replace(
            `__CODEBLOCK_${index}__`,
            `<pre class="my-3 p-3 rounded-lg bg-gray-100 dark:bg-gray-800 overflow-x-auto"><code class="text-sm font-mono text-gray-800 dark:text-gray-200">${code}</code></pre>`
        );
    });
    
    // Convert double line breaks to paragraphs
    formatted = formatted.split(/\n\n+/).map(para => {
        para = para.trim();
        if (!para) return '';
        // Don't wrap if already wrapped in list or code block
        if (para.startsWith('<ul') || para.startsWith('<pre') || para.startsWith('<li')) {
            return para;
        }
        return `<p class="my-2 leading-relaxed">${para}</p>`;
    }).join('');
    
    // Convert single line breaks to <br> (but not inside lists or code)
    formatted = formatted.replace(/(?<!<\/li>)\n(?!<li)/g, '<br />');
    
    return formatted;
};

const typeText = async (text: string) => {
    if (!props.showTyping) {
        displayedText.value = formatText(text);
        emit('typingComplete');
        return;
    }

    isTyping.value = true;
    displayedText.value = '';
    
    const plainText = text;
    let currentIndex = 0;

    const typeChar = async () => {
        if (currentIndex < plainText.length) {
            currentIndex++;
            
            // Update displayed text with formatting applied to current portion
            const currentText = plainText.substring(0, currentIndex);
            displayedText.value = formatText(currentText);
            
            // Scroll to bottom during typing
            await nextTick();
            
            setTimeout(typeChar, props.typingSpeed);
        } else {
            isTyping.value = false;
            emit('typingComplete');
        }
    };

    typeChar();
};

watch(() => props.text, (newText) => {
    if (newText) {
        typeText(newText);
    } else {
        displayedText.value = '';
        isTyping.value = false;
    }
}, { immediate: true });

onMounted(() => {
    if (props.text) {
        typeText(props.text);
    }
});
</script>

<template>
    <div class="formatted-text">
        <div
            v-if="displayedText"
            v-html="displayedText"
            class="prose prose-sm max-w-none"
        />
        <span
            v-if="isTyping"
            class="inline-block w-0.5 h-4 bg-current ml-1 animate-pulse"
            aria-hidden="true"
        />
    </div>
</template>

<style scoped>
.formatted-text :deep(ul) {
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
    margin-left: 1rem;
    list-style-type: disc;
}

.formatted-text :deep(ul li) {
    margin-top: 0.375rem;
    margin-bottom: 0.375rem;
}

.formatted-text :deep(ol) {
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
    margin-left: 1rem;
    list-style-type: decimal;
}

.formatted-text :deep(ol li) {
    margin-top: 0.375rem;
    margin-bottom: 0.375rem;
}

.formatted-text :deep(li) {
    font-size: 0.875rem;
    line-height: 1.625;
}

.formatted-text :deep(strong) {
    font-weight: 600;
}

.formatted-text :deep(em) {
    font-style: italic;
}

.formatted-text :deep(code) {
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    background-color: rgb(243 244 246);
    font-size: 0.875rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

.dark .formatted-text :deep(code) {
    background-color: rgb(31 41 55);
}

.formatted-text :deep(pre) {
    margin-top: 0.75rem;
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    border-radius: 0.5rem;
    background-color: rgb(243 244 246);
    overflow-x: auto;
}

.dark .formatted-text :deep(pre) {
    background-color: rgb(31 41 55);
}

.formatted-text :deep(pre code) {
    background-color: transparent;
    padding: 0;
}

.formatted-text :deep(p) {
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
    line-height: 1.625;
}

.formatted-text :deep(p:first-child) {
    margin-top: 0;
}

.formatted-text :deep(p:last-child) {
    margin-bottom: 0;
}

.formatted-text :deep(br) {
    line-height: 1.625;
}
</style>

