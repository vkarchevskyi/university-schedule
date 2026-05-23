<script setup lang="ts">
import { useRoute } from 'vue-router'

import AppButton from '@/components/atoms/AppButton.vue'
import StateMessage from '@/components/atoms/StateMessage.vue'
import ConfirmActionButton from '@/components/molecules/ConfirmActionButton.vue'
import AdminLayout from '@/components/organisms/AdminLayout.vue'
import { useAdminEntities } from '@/composables/useAdminEntities'
import { useAdminI18n } from '@/composables/useI18n'
import { entityConfigByKey } from '@/config/adminEntities'

const route = useRoute()
const config = entityConfigByKey(String(route.params.entity))
const state = config === undefined ? null : useAdminEntities(config)
const { t } = useAdminI18n()
</script>

<template>
  <AdminLayout>
    <StateMessage v-if="config === undefined || state === null" tone="error" :title="t.notFound" />
    <section v-else class="admin-entity-page">
      <header class="admin-page-header">
        <div>
          <h1>{{ config.title }}</h1>
          <p>{{ t.entityIntro }}</p>
        </div>
        <AppButton variant="primary" data-testid="create-entity" @click="state.startCreate">
          {{ t.add }}
        </AppButton>
      </header>

      <StateMessage v-if="state.error.value" tone="error" :title="state.error.value" />
      <StateMessage v-else-if="state.isLoading.value" :title="t.loading" />
      <div v-else class="admin-table-wrap">
        <table class="admin-table" data-testid="entity-table">
          <thead>
            <tr>
              <th v-for="column in config.columns" :key="column.key" scope="col">
                {{ column.label }}
              </th>
              <th scope="col">{{ t.actions }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="state.items.value.length === 0">
              <td :colspan="config.columns.length + 1">{{ t.noRecords }}</td>
            </tr>
            <tr v-for="item in state.items.value" v-else :key="item.id">
              <td v-for="column in config.columns" :key="column.key">
                {{
                  column.format
                    ? column.format(item[column.key], item)
                    : state.displayValue(item, column.key)
                }}
              </td>
              <td>
                <div class="table-actions">
                  <AppButton data-testid="edit-entity" @click="state.startEdit(item)">
                    {{ t.edit }}
                  </AppButton>
                  <ConfirmActionButton
                    :message="t.deleteConfirm"
                    testid="delete-entity"
                    @confirm="state.remove(item)"
                  >
                    {{ t.delete }}
                  </ConfirmActionButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="Object.keys(state.form.value.values).length > 0" class="modal-backdrop">
        <form class="modal-panel" data-testid="entity-form" @submit.prevent="state.save">
          <header class="modal-panel__header">
            <h2>{{ state.editing.value ? t.edit : t.add }}</h2>
            <AppButton variant="ghost" @click="state.closeForm">{{ t.close }}</AppButton>
          </header>
          <StateMessage
            v-if="Object.keys(state.form.value.errors).length > 0"
            tone="error"
            :title="t.validationFailed"
            data-testid="entity-validation-summary"
          >
            <ul>
              <li v-for="(message, field) in state.form.value.errors" :key="field">
                {{ message }}
              </li>
            </ul>
          </StateMessage>
          <div class="entity-form-grid">
            <label v-for="field in config.fields" :key="field.key" class="field">
              <span class="field__label">{{ field.label }}</span>
              <select
                v-if="field.type === 'select'"
                v-model="state.form.value.values[field.key]"
                class="field__control"
                :required="field.required"
              >
                <option value="">{{ t.chooseValue }}</option>
                <option
                  v-for="option in state.fieldOptions(field)"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
              <input
                v-else
                v-model="state.form.value.values[field.key]"
                class="field__control"
                :type="field.type"
                :required="field.required"
              />
              <small v-if="state.form.value.errors[field.key]" class="field-error">
                {{ state.form.value.errors[field.key] }}
              </small>
            </label>
          </div>
          <footer class="modal-panel__footer">
            <AppButton type="submit" variant="primary" :disabled="state.isSaving.value">
              {{ t.save }}
            </AppButton>
          </footer>
        </form>
      </div>
    </section>
  </AdminLayout>
</template>
