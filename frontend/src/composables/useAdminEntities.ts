import { computed, onMounted, ref } from 'vue'

import { createEntity, deleteEntity, listEntities, updateEntity } from '@/api/adminEntities'
import { ApiError } from '@/api/http'
import { useAdminI18n } from '@/composables/useI18n'
import type {
  AdminEntity,
  EntityConfig,
  EntityField,
  EntityFormState,
  EntityLookups,
} from '@/types/adminEntities'

export function useAdminEntities(config: EntityConfig) {
  const { t } = useAdminI18n()
  const items = ref<AdminEntity[]>([])
  const lookups = ref<EntityLookups>({})
  const editing = ref<AdminEntity | null>(null)
  const form = ref<EntityFormState>({ values: {}, errors: {} })
  const isLoading = ref(true)
  const isSaving = ref(false)
  const error = ref<string | null>(null)

  const requiredLookups = computed(() =>
    Array.from(new Set(config.fields.map((field) => field.lookup).filter(Boolean))),
  )

  onMounted(load)

  async function load(): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      const [listResponse] = await Promise.all([listEntities(config.endpoint), loadLookups()])
      items.value = listResponse.items
    } catch {
      error.value = t.value.apiError
    } finally {
      isLoading.value = false
    }
  }

  async function loadLookups(): Promise<void> {
    const loaded: EntityLookups = {}

    await Promise.all(
      requiredLookups.value.map(async (lookup) => {
        if (lookup === undefined) {
          return
        }

        const endpoint = lookupEndpoint(lookup)
        if (endpoint === null) {
          return
        }

        loaded[lookup] = (await listEntities(endpoint)).items
      }),
    )

    lookups.value = loaded
  }

  function startCreate(): void {
    editing.value = null
    form.value = { values: initialValues(), errors: {} }
  }

  function startEdit(item: AdminEntity): void {
    editing.value = item
    form.value = { values: initialValues(item), errors: {} }
  }

  function closeForm(): void {
    editing.value = null
    form.value = { values: {}, errors: {} }
  }

  async function save(): Promise<void> {
    isSaving.value = true
    form.value.errors = {}

    try {
      const payload = payloadValues()

      if (editing.value?.id === undefined) {
        await createEntity(config.endpoint, payload)
      } else {
        await updateEntity(config.endpoint, editing.value.id, payload)
      }

      closeForm()
      await load()
    } catch (exception) {
      if (exception instanceof ApiError && exception.violations.length > 0) {
        form.value.errors = Object.fromEntries(
          exception.violations.map((violation) => [violation.propertyPath, violation.message]),
        )
      } else {
        error.value = t.value.apiError
      }
    } finally {
      isSaving.value = false
    }
  }

  async function remove(item: AdminEntity): Promise<void> {
    if (item.id === undefined) {
      return
    }

    await deleteEntity(config.endpoint, item.id)
    await load()
  }

  function fieldOptions(field: EntityField): Array<{ value: string | number; label: string }> {
    if (field.options !== undefined) {
      return field.options
    }

    if (field.lookup === undefined) {
      return []
    }

    return (lookups.value[field.lookup] ?? []).map((item) => ({
      value: item.id ?? '',
      label: labelFor(item),
    }))
  }

  function displayValue(item: AdminEntity, key: string): string {
    const field = config.fields.find((candidate) => candidate.key === key)
    const value = item[key]

    if (field?.options !== undefined) {
      return field.options.find((option) => option.value === value)?.label ?? String(value ?? '')
    }

    if (field?.lookup !== undefined && typeof value === 'number') {
      return labelFor((lookups.value[field.lookup] ?? []).find((lookup) => lookup.id === value))
    }

    return String(value ?? '')
  }

  function initialValues(item: AdminEntity = {}): Record<string, string | number | boolean> {
    return Object.fromEntries(
      config.fields.map((field) => {
        const value = item[field.key]
        if (typeof value === 'number' || typeof value === 'string' || typeof value === 'boolean') {
          return [field.key, value]
        }

        return [field.key, field.type === 'boolean' ? false : '']
      }),
    )
  }

  function payloadValues(): AdminEntity {
    return Object.fromEntries(
      config.fields.map((field) => {
        const value = form.value.values[field.key]

        if (field.type === 'boolean') {
          return [field.key, value === true]
        }

        if (field.type === 'number' || (field.type === 'select' && field.lookup !== undefined)) {
          const numericValue = Number(value)
          if (Number.isFinite(numericValue) && value !== '') {
            return [field.key, numericValue]
          }
        }

        return [field.key, value]
      }),
    )
  }

  return {
    items,
    form,
    editing,
    isLoading,
    isSaving,
    error,
    load,
    startCreate,
    startEdit,
    closeForm,
    save,
    remove,
    fieldOptions,
    displayValue,
  }
}

function lookupEndpoint(lookup: keyof EntityLookups): string | null {
  return {
    academicYears: '/api/admin/academic-years',
    semesters: '/api/admin/semesters',
    groups: '/api/admin/groups',
    teachers: '/api/admin/teachers',
    subjects: '/api/admin/subjects',
  }[lookup]
}

export function labelFor(item: AdminEntity | undefined): string {
  if (item === undefined) {
    return ''
  }

  if (typeof item.name === 'string') {
    return item.name
  }

  if (typeof item.firstName === 'string' && typeof item.lastName === 'string') {
    return `${item.firstName} ${item.lastName}`
  }

  if (typeof item.number === 'number') {
    return `#${item.number}`
  }

  return String(item.id ?? '')
}
