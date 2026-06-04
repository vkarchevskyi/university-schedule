export interface ResourceCollection<T> {
  items: T[]
}

export type FieldType = 'text' | 'number' | 'date' | 'time' | 'select' | 'boolean'

export interface EntityField {
  key: string
  labelKey: string
  type: FieldType
  required?: boolean
  options?: Array<{ value: string | number; labelKey: string }>
  lookup?: 'academicYears' | 'semesters' | 'groups' | 'teachers' | 'subjects'
}

export interface EntityConfig {
  key: string
  titleKey: string
  hintKey: string
  routeName: string
  endpoint: string
  fields: EntityField[]
  columns: Array<{ key: string; labelKey: string; format?: (value: unknown, row: AdminEntity) => string }>
}

export type AdminEntity = Record<string, unknown> & { id?: number }

export type EntityLookups = Partial<Record<NonNullable<EntityField['lookup']>, AdminEntity[]>>

export interface EntityFormState {
  values: Record<string, string | number | boolean>
  errors: Record<string, string>
}
