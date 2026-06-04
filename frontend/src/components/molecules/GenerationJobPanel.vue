<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import StatusBadge from '@/components/atoms/StatusBadge.vue'
import { useAdminI18n } from '@/composables/useI18n'

const props = defineProps<{
  status: string
  qualityScore: number | null
  qualityStatus?: string | null
  diagnostics?: Record<string, unknown> | string[] | null
  errorMessage?: string | null
  generatedId: number | null
  openLabel: string
  testid: string
  openTestid?: string
}>()

const emit = defineEmits<{
  open: [id: number]
}>()

const { t } = useAdminI18n()

function diagnosticItems(): string[] {
  if (Array.isArray(props.diagnostics)) {
    return props.diagnostics.map(String)
  }

  if (props.diagnostics === null || props.diagnostics === undefined) {
    return []
  }

  return Object.entries(props.diagnostics).map(([key, value]) => `${key}: ${String(value)}`)
}
</script>

<template>
  <StateMessage :title="t.generationStatus" :data-testid="testid">
    <div class="generation-job">
      <div class="generation-job__summary">
        <StatusBadge :tone="status === 'failed' ? 'warning' : 'info'">
          {{ status }}
        </StatusBadge>
        <p v-if="qualityScore !== null">{{ t.qualityScore }}: {{ qualityScore }}</p>
        <p v-if="qualityStatus">{{ t.qualityStatus }}: {{ qualityStatus }}</p>
      </div>
      <p v-if="errorMessage" class="field-error">{{ errorMessage }}</p>
      <section>
        <strong>{{ diagnosticItems().length > 0 ? t.whatToFixNext : t.diagnostics }}</strong>
        <ul v-if="diagnosticItems().length > 0">
          <li v-for="item in diagnosticItems()" :key="item">{{ item }}</li>
        </ul>
        <p v-else>{{ t.noDiagnostics }}</p>
      </section>
      <AppButton
        v-if="generatedId !== null"
        :data-testid="openTestid ?? 'open-generated-draft'"
        @click="emit('open', generatedId)"
      >
        {{ openLabel }}
      </AppButton>
    </div>
  </StateMessage>
</template>
