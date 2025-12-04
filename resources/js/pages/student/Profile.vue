<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { useMediaQuery } from '@vueuse/core';
import { type BreadcrumbItem } from '@/types';

interface LearningPreference {
    id?: number;
    grade_level: string | null;
    subjects: string[];
    preferred_difficulty: 'Beginner' | 'Intermediate' | 'Advanced' | null;
    learning_style: 'visual' | 'reading' | 'practice' | 'mixed' | null;
    goals: string | null;
}

const loading = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const success = ref<string | null>(null);

const form = ref<LearningPreference>({
    grade_level: null,
    subjects: [],
    preferred_difficulty: null,
    learning_style: null,
    goals: null,
});

const subjectsInput = ref('');

const difficultyOptions = [
    { value: 'Beginner', label: 'Beginner' },
    { value: 'Intermediate', label: 'Intermediate' },
    { value: 'Advanced', label: 'Advanced' },
];

const learningStyleOptions = [
    { value: 'visual', label: 'Visual' },
    { value: 'reading', label: 'Reading' },
    { value: 'practice', label: 'Practice' },
    { value: 'mixed', label: 'Mixed' },
];

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => {
    return (user.value?.role as 'student' | 'teacher') || 'student';
});

const fetchPreferences = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch('/api/student/preferences', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin', // Include cookies for session auth
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to load preferences');
        }

        const result = await response.json();
        const data = result.data;

        form.value = {
            grade_level: data.grade_level,
            subjects: data.subjects || [],
            preferred_difficulty: data.preferred_difficulty,
            learning_style: data.learning_style,
            goals: data.goals,
        };

        // Convert subjects array to comma-separated string for input
        subjectsInput.value = form.value.subjects.join(', ');
    } catch (err: any) {
        error.value = err.message || 'Failed to load preferences';
        console.error('Error fetching preferences:', err);
    } finally {
        loading.value = false;
    }
};

const handleSubjectsChange = () => {
    // Convert comma-separated string to array
    form.value.subjects = subjectsInput.value
        .split(',')
        .map((s) => s.trim())
        .filter((s) => s.length > 0);
};

const submitForm = async () => {
    saving.value = true;
    error.value = null;
    success.value = null;

    // Update subjects from input
    handleSubjectsChange();

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch('/api/student/preferences', {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin', // Include cookies for session auth
            body: JSON.stringify({
                grade_level: form.value.grade_level || null,
                subjects: form.value.subjects,
                preferred_difficulty: form.value.preferred_difficulty || null,
                learning_style: form.value.learning_style || null,
                goals: form.value.goals || null,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const errorMessage = errorData.message ||
                (errorData.errors && Object.values(errorData.errors).flat()[0]) ||
                'Failed to update preferences';
            throw new Error(errorMessage);
        }

        const result = await response.json();
        success.value = result.message || 'Learning preferences updated successfully!';

        // Clear success message after 3 seconds
        setTimeout(() => {
            success.value = null;
        }, 3000);
    } catch (err: any) {
        error.value = err.message || 'Failed to update preferences';
        console.error('Error updating preferences:', err);
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchPreferences();
});
</script>

<template>
    <Head title="Learning Preferences" />

    <!-- Mobile Layout -->
    <template v-if="isMobile">
        <div class="mx-auto w-full max-w-3xl px-4 pt-20 pb-20 sm:px-6 sm:pt-6 sm:pb-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-brand-primary">
                Learning Preferences
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                Customize your learning experience to get personalized recommendations.
            </p>
        </div>

        <!-- Success Message -->
        <div
            v-if="success"
            class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800"
        >
            {{ success }}
        </div>

        <!-- Error Message -->
        <div
            v-if="error"
            class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800"
        >
            {{ error }}
        </div>

        <!-- Loading State -->
        <div
            v-if="loading"
            class="flex items-center justify-center py-12"
        >
            <div class="text-gray-500">
                Loading preferences...
            </div>
        </div>

        <!-- Form -->
        <form
            v-else
            class="space-y-6 rounded-xl bg-white p-6 shadow-sm"
            @submit.prevent="submitForm"
        >
            <!-- Grade Level -->
            <div>
                <Label for="grade_level">Grade Level</Label>
                <Input
                    id="grade_level"
                    v-model="form.grade_level"
                    type="text"
                    placeholder="e.g., Grade 12, Senior High School"
                    class="mt-1"
                />
                <p class="mt-1 text-xs text-gray-500">
                    Enter your current grade level or educational level
                </p>
            </div>

            <!-- Subjects -->
            <div>
                <Label for="subjects">Subjects</Label>
                <Input
                    id="subjects"
                    v-model="subjectsInput"
                    type="text"
                    placeholder="e.g., Science, Mathematics, English"
                    class="mt-1"
                    @input="handleSubjectsChange"
                />
                <p class="mt-1 text-xs text-gray-500">
                    Enter subjects separated by commas
                </p>
            </div>

            <!-- Preferred Difficulty -->
            <div>
                <Label for="preferred_difficulty">Preferred Difficulty</Label>
                <select
                    id="preferred_difficulty"
                    v-model="form.preferred_difficulty"
                    class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option :value="null">
                        Select difficulty level
                    </option>
                    <option
                        v-for="option in difficultyOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    Choose your preferred difficulty level for learning resources
                </p>
            </div>

            <!-- Learning Style -->
            <div>
                <Label for="learning_style">Learning Style</Label>
                <select
                    id="learning_style"
                    v-model="form.learning_style"
                    class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option :value="null">
                        Select learning style
                    </option>
                    <option
                        v-for="option in learningStyleOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    How do you prefer to learn?
                </p>
            </div>

            <!-- Goals -->
            <div>
                <Label for="goals">Learning Goals</Label>
                <textarea
                    id="goals"
                    v-model="form.goals"
                    rows="4"
                    placeholder="Describe your learning goals and what you want to achieve..."
                    class="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                />
                <p class="mt-1 text-xs text-gray-500">
                    Tell us about your learning objectives and goals
                </p>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-3 pt-4">
                <Button
                    type="submit"
                    :disabled="saving"
                    class="bg-brand-primary hover:bg-brand-primary-hover"
                >
                    <span v-if="!saving">Save Preferences</span>
                    <span v-else>Saving...</span>
                </Button>
            </div>
        </form>
    </div>

    <!-- Bottom Navigation (Mobile Only) -->
    <BottomNav :role="userRole" />
</template>
