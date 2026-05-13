const isoDateLength = 10
const daysInWeek = 7

export function toIsoDate(date: Date): string {
  return date.toISOString().slice(0, isoDateLength)
}

export function mondayOfWeek(date: Date): Date {
  const result = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()))
  const day = result.getUTCDay()
  const offset = day === 0 ? -6 : 1 - day
  result.setUTCDate(result.getUTCDate() + offset)

  return result
}

export function currentWeekStart(): string {
  return toIsoDate(mondayOfWeek(new Date()))
}

export function addWeeks(weekStart: string, weeks: number): string {
  const date = parseIsoDate(weekStart)
  date.setUTCDate(date.getUTCDate() + weeks * daysInWeek)

  return toIsoDate(date)
}

export function weekDates(weekStart: string): string[] {
  const start = parseIsoDate(weekStart)

  return Array.from({ length: daysInWeek }, (_, index) => {
    const date = new Date(start)
    date.setUTCDate(start.getUTCDate() + index)

    return toIsoDate(date)
  })
}

export function formatDisplayDate(value: string): string {
  return new Intl.DateTimeFormat('uk-UA', { day: '2-digit', month: '2-digit' }).format(parseIsoDate(value))
}

function parseIsoDate(value: string): Date {
  return new Date(`${value}T00:00:00.000Z`)
}
