export interface ResourceCollection<T> {
  items: T[]
}

export type FieldType = 'text' | 'number' | 'date' | 'time' | 'select'

export interface EntityField {
  key: string
  label: string
  type: FieldType
  required?: boolean
  options?: Array<{ value: string | number; label: string }>
  lookup?: 'academicYears' | 'semesters' | 'groups' | 'teachers' | 'subjects'
}

export interface EntityConfig {
  key: string
  title: string
  routeName: string
  endpoint: string
  fields: EntityField[]
  columns: Array<{ key: string; label: string; format?: (value: unknown, row: AdminEntity) => string }>
}

export type AdminEntity = Record<string, unknown> & { id?: number }

export type EntityLookups = Partial<Record<NonNullable<EntityField['lookup']>, AdminEntity[]>>

export interface EntityFormState {
  values: Record<string, string | number>
  errors: Record<string, string>
}
