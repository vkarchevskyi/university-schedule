<script setup lang="ts">
import StateMessage from '@/components/atoms/StateMessage.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { useAdminActionLogs } from '@/composables/useAdminActionLogs'
import { useAdminI18n } from '@/composables/useI18n'

const { t } = useAdminI18n()
const { logs, isLoading, error } = useAdminActionLogs()

function userName(log: { user: { firstName: string; lastName: string } }): string {
  return `${log.user.firstName} ${log.user.lastName}`
}

function payloadText(payload: Record<string, unknown> | null): string {
  return payload === null ? t.value.noAuditPayload : JSON.stringify(payload, null, 2)
}
</script>

<template>
  <AdminLayout>
    <section class="admin-dashboard action-log-page">
      <h1>{{ t.actionLogTitle }}</h1>
      <StateMessage v-if="error" tone="error" :title="error" />
      <StateMessage v-else-if="isLoading" :title="t.loading" />
      <StateMessage v-else-if="logs.length === 0" :title="t.noActionLogs" data-testid="no-action-logs" />
      <div v-else class="admin-table-wrap">
        <table class="admin-table action-log-table" data-testid="action-log-table">
          <thead>
            <tr>
              <th>{{ t.date }}</th>
              <th>{{ t.author }}</th>
              <th>{{ t.action }}</th>
              <th>{{ t.entity }}</th>
              <th>{{ t.before }}</th>
              <th>{{ t.after }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td>{{ log.createdAt }}</td>
              <td>
                <strong>{{ userName(log) }}</strong>
                <span>{{ log.user.email }}</span>
              </td>
              <td>{{ log.action }}</td>
              <td>{{ log.entityType }} #{{ log.entityId }}</td>
              <td><pre>{{ payloadText(log.beforePayload) }}</pre></td>
              <td><pre>{{ payloadText(log.afterPayload) }}</pre></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </AdminLayout>
</template>
