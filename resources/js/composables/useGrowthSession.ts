import {IGrowthSession} from "../types"
import {ref} from "vue"
import {GrowthSession} from "../classes/GrowthSession"

export function useGrowthSession(growthSessionJson: IGrowthSession) {
    const growthSession = ref<GrowthSession>(new GrowthSession(growthSessionJson))
    const isProcessing = ref<boolean>(false)

    async function joinGrowthSession() {
        isProcessing.value = true
        await growthSession.value.join()
        isProcessing.value = false
    }

    async function leaveGrowthSession() {
        isProcessing.value = true
        await growthSession.value.leave()
        isProcessing.value = false
    }

    async function watchGrowthSession() {
        isProcessing.value = true
        await growthSession.value.watch()
        isProcessing.value = false
    }

    return {
        watchGrowthSession,
        leaveGrowthSession,
        joinGrowthSession,
        isProcessing,
        growthSession
    }
}
