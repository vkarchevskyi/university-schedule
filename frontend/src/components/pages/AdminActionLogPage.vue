<script setup lang="ts">
import { computed, ref } from 'vue'

import StateMessage from '@/components/atoms/StateMessage.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { useAdminActionLogs } from '@/composables/useAdminActionLogs'
import { useAdminI18n } from '@/composables/useI18n'

const { t } = useAdminI18n()
const { logs, isLoading, error } = useAdminActionLogs()
const authorFilter = ref('')
const actionFilter = ref('')
const entityFilter = ref('')
const dateFilter = ref('')
const filteredLogs = computed(() =>
  logs.value.filter((log) => {
    const author = `${userName(log)} ${log.user.email}`.toLocaleLowerCase()
    const action = log.action.toLocaleLowerCase()
    const entity = `${log.entityType} #${log.entityId}`.toLocaleLowerCase()

    return (
      author.includes(authorFilter.value.trim().toLocaleLowerCase()) &&
      action.includes(actionFilter.value.trim().toLocaleLowerCase()) &&
      entity.includes(entityFilter.value.trim().toLocaleLowerCase()) &&
      (dateFilter.value === '' || log.createdAt.startsWith(dateFilter.value))
    )
  }),
)

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
      <section v-else class="audit-filter-bar">
        <label class="field">
          <span class="field__label">{{ t.author }}</span>
          <input v-model="authorFilter" class="field__control" type="search" />
        </label>
        <label class="field">
          <span class="field__label">{{ t.action }}</span>
          <input v-model="actionFilter" class="field__control" type="search" />
        </label>
        <label class="field">
          <span class="field__label">{{ t.entity }}</span>
          <input v-model="entityFilter" class="field__control" type="search" />
        </label>
        <label class="field">
          <span class="field__label">{{ t.date }}</span>
          <input v-model="dateFilter" class="field__control" type="date" />
        </label>
      </section>
      <div v-if="!isLoading && logs.length > 0" class="admin-table-wrap">
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
            <tr v-for="log in filteredLogs" :key="log.id">
              <td>{{ log.createdAt }}</td>
              <td>
                <strong>{{ userName(log) }}</strong>
                <span>{{ log.user.email }}</span>
              </td>
              <td>{{ log.action }}</td>
              <td>{{ log.entityType }} #{{ log.entityId }}</td>
              <td>
                <details>
                  <summary>{{ t.payloadDetails }}</summary>
                  <pre>{{ payloadText(log.beforePayload) }}</pre>
                </details>
              </td>
              <td>
                <details>
                  <summary>{{ t.payloadDetails }}</summary>
                  <pre>{{ payloadText(log.afterPayload) }}</pre>
                </details>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </AdminLayout>
</template>
