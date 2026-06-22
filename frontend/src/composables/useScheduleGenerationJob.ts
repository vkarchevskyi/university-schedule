import { onUnmounted, ref } from 'vue'

import { subscribeToGenerationJob } from '@/api/generationNotifications'
import { generateSchedule, getGenerationJob } from '@/api/adminSchedule'
import type { ScheduleGenerationJob } from '@/types/adminSchedule'

export function useScheduleGenerationJob() {
  const generationJob = ref<ScheduleGenerationJob | null>(null)
  const isGenerating = ref(false)
  let stopGenerationNotifications: (() => void) | null = null

  onUnmounted(() => stopGenerationNotifications?.())

  async function startGeneration(
    semesterId: number,
    scheduleId?: number,
    onCompleted?: (job: ScheduleGenerationJob) => Promise<void> | void,
  ): Promise<ScheduleGenerationJob> {
    isGenerating.value = true
    generationJob.value = await generateSchedule(semesterId, scheduleId)
    await waitForGeneration(generationJob.value.id, onCompleted)

    return generationJob.value
  }

  async function waitForGeneration(
    jobId: string,
    onCompleted?: (job: ScheduleGenerationJob) => Promise<void> | void,
  ): Promise<void> {
    await new Promise<void>((resolve) => {
      let resolved = false
      const fallbackTimeout = window.setTimeout(startPollingFallback, 10000)

      function finish(): void {
        if (resolved) {
          return
        }
        resolved = true
        window.clearTimeout(fallbackTimeout)
        stopGenerationNotifications?.()
        stopGenerationNotifications = null
        isGenerating.value = false
        resolve()
      }

      async function startPollingFallback(): Promise<void> {
        if (resolved) {
          return
        }
        await pollGeneration(jobId, onCompleted)
        finish()
      }

      stopGenerationNotifications = subscribeToGenerationJob<ScheduleGenerationJob>(
        'schedule_generation_job',
        jobId,
        async ({ job }) => {
          generationJob.value = job

          if (job.status === 'completed' || job.status === 'failed') {
            if (job.status === 'completed' && onCompleted) {
              await onCompleted(job)
            }
            finish()
          }
        },
        startPollingFallback,
      )
    })
  }

  async function pollGeneration(
    jobId: string,
    onCompleted?: (job: ScheduleGenerationJob) => Promise<void> | void,
  ): Promise<void> {
    const terminal = new Set(['completed', 'failed'])

    for (let attempt = 0; attempt < 10; attempt += 1) {
      const job = await getGenerationJob(jobId)
      generationJob.value = job

      if (terminal.has(job.status)) {
        if (job.status === 'completed' && onCompleted) {
          await onCompleted(job)
        }
        return
      }

      await new Promise((resolve) => window.setTimeout(resolve, 1000))
    }
  }

  return {
    generationJob,
    isGenerating,
    startGeneration,
  }
}
