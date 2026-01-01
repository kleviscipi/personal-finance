<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    suggestions: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const inputValue = ref('');
const isOpen = ref(false);

const normalizedSuggestions = computed(() => {
    return (props.suggestions || [])
        .map((item) => (typeof item === 'string' ? item : item.name))
        .filter((name) => name);
});

const selectedSet = computed(() => {
    return new Set(
        (props.modelValue || []).map((name) => name.toLowerCase()),
    );
});

const filteredOptions = computed(() => {
    const query = inputValue.value.trim().toLowerCase();
    return normalizedSuggestions.value
        .filter((name) => !selectedSet.value.has(name.toLowerCase()))
        .filter((name) => !query || name.toLowerCase().includes(query))
        .slice(0, 8);
});

const commitTag = (value) => {
    const tag = value.trim();
    if (!tag) {
        return;
    }

    const lower = tag.toLowerCase();
    if (selectedSet.value.has(lower)) {
        inputValue.value = '';
        return;
    }

    emit('update:modelValue', [...props.modelValue, tag]);
    inputValue.value = '';
};

const removeTag = (index) => {
    const next = [...props.modelValue];
    next.splice(index, 1);
    emit('update:modelValue', next);
};

const onKeydown = (event) => {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        commitTag(inputValue.value);
        return;
    }

    if (event.key === 'Backspace' && !inputValue.value && props.modelValue.length) {
        event.preventDefault();
        removeTag(props.modelValue.length - 1);
    }
};

const onOptionClick = (name) => {
    commitTag(name);
    isOpen.value = false;
};

watch(
    () => props.modelValue,
    () => {
        if (!props.modelValue.length) {
            inputValue.value = '';
        }
    },
);
</script>

<template>
    <div class="relative">
        <div
            class="flex min-h-[40px] flex-wrap items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-2 py-1.5 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500"
        >
            <span
                v-for="(tag, index) in modelValue"
                :key="`${tag}-${index}`"
                class="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-700"
            >
                {{ tag }}
                <button
                    type="button"
                    class="text-slate-500 hover:text-slate-700"
                    @click="removeTag(index)"
                >
                    &times;
                </button>
            </span>
            <input
                v-model="inputValue"
                type="text"
                class="h-8 min-w-[120px] flex-1 border-0 bg-transparent text-sm text-slate-700 outline-none focus:ring-0 focus:border-transparent"
                :placeholder="placeholder"
                @keydown="onKeydown"
                @focus="isOpen = true"
                @blur="isOpen = false"
            >
        </div>
        <div
            v-if="isOpen && filteredOptions.length"
            class="absolute z-20 mt-2 w-full rounded-xl border border-slate-200 bg-white shadow-lg"
        >
            <button
                v-for="option in filteredOptions"
                :key="option"
                type="button"
                class="flex w-full items-center px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                @mousedown.prevent="onOptionClick(option)"
            >
                {{ option }}
            </button>
        </div>
    </div>
</template>
