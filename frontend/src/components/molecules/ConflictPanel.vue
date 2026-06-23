<script setup lang="ts">
import StateMessage from '@/components/atoms/StateMessage.vue'
import { useAdminI18n } from '@/composables/useI18n'
import { translateScheduleConflict } from '@/utils/scheduleConflicts'

const props = withDefaults(
  defineProps<{
    conflicts: Array<{ type: string; message: string; entryIds: number[] }>
    context?: 'schedule' | 'exam'
  }>(),
  {
    context: 'schedule',
  },
)

const { t, locale } = useAdminI18n()

function conflictMessage(conflict: { type: string; message: string }): string {
  return translateScheduleConflict(conflict.type, conflict.message, locale.locale, props.context)
}
</script>

<template>
  <StateMessage v-if="conflicts.length > 0" tone="error" :title="t.conflictPanel" data-testid="conflict-panel">
    <ul class="conflict-list">
      <li v-for="conflict in conflicts" :key="`${conflict.type}-${conflict.message}`">
        <strong>{{ conflictMessage(conflict) }}</strong>
        <small v-if="conflict.entryIds.length > 0">
          {{ t.affectedEntries }}: {{ conflict.entryIds.map((id) => `#${id}`).join(', ') }}
        </small>
      </li>
    </ul>
  </StateMessage>
</template>
