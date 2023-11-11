<script lang="ts" setup>
import {IGrowthSession, IUser, IValidationError} from "../types"
import {DateTime} from "../classes/DateTime"
import {GrowthSession} from "../classes/GrowthSession"
import {GrowthSessionApi} from "../services/GrowthSessionApi"
import VAvatar from "./VAvatar.vue"
import {computed, ref} from "vue"

interface IProps {
    user?: IUser;
    growthSessionJson: IGrowthSession;
}

const props = defineProps<IProps>()
const emit = defineEmits(["submitted"])

const growthSession = ref<GrowthSession>(new GrowthSession(props.growthSessionJson))
const validationErrors = ref<IValidationError | null>(null)

const date = computed(() => `${DateTime.parseByDate(growthSession.value.date).format("MMM-DD")}`)
const time = computed(() => `${growthSession.value.startTime} - ${growthSession.value.endTime}`)

async function deleteGrowthSession() {
    await growthSession.value.delete()
    window.location.assign("/")
}

async function updateGrowthSession() {
    try {
        const payload = {...growthSession.value}
        if (growthSession.value.isLimitless) {
            payload.attendee_limit = null
        }
        let updatedGrowthSession: IGrowthSession = await GrowthSessionApi.update(growthSession.value, payload)
        emit("submitted", updatedGrowthSession)
        window.history.back()
    } catch (e) {
        onRequestFailed(e)
    }
}

function onRequestFailed(exception: any) {
    if (exception.response?.status === 422) {
        console.log(exception.response.data.errors)
        validationErrors.value = exception.response.data
    } else {
        alert("Something went wrong :(")
    }
}

function getError(field: string): string {
    let errors = validationErrors.value?.errors[field]
    return errors ? errors[0] : ""
}
</script>

<template>
    <div class="max-w-5xl text-gray-600">
        <div class="mb-8 flex flex-col lg:flex-row lg:justify-between items-center">
            <h2 class="text-2xl lg:text-3xl font-sans font-light flex flex-1 items-center text-gray-700 pr-6">
                <v-avatar :alt="`${growthSession.owner.name}'s Avatar`" :src="growthSession.owner.avatar" class="mr-4"/>
                <input class="flex-1 shadow appearance-none border rounded w-full px-3"
                       placeholder="Please enter a growth session title"
                       type="text"
                       maxlength="45"
                       v-model="growthSession.title">
            </h2>
            <div>
                <button
                    @click.stop="updateGrowthSession"
                    class="update-button w-32 bg-orange-500 hover:bg-orange-700 focus:bg-orange-700 text-white font-bold py-2 px-4 rounded">
                    Save
                </button>
                <button
                    @click.stop="deleteGrowthSession"
                    class="delete-button w-16 bg-red-500 hover:bg-red-700 focus:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    <i aria-hidden="true" class="fa fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="flex flex-col lg:flex-row flex-wrap">
            <div class="flex-1 mr-2 max-w-5xl">
                <h3 class="text-2xl font-sans font-light mb-3 text-gray-700">Topic</h3>
                <textarea :class="{'error-outline': getError('topic')}"
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-slate-700 leading-tight focus:outline-none focus:shadow-outline"
                          id="topic"
                          placeholder="What is this growth session about?"
                          rows="4"
                          v-model="growthSession.topic"/>
            </div>
            <div class="flex-none max-w-md">
                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-gray-700">Location:</h3>
                    <textarea :class="{'error-outline': getError('location')}"
                              class="shadow appearance-none border rounded w-full py-2 px-3 text-slate-700 leading-tight focus:outline-none focus:shadow-outline"
                              id="location"
                              placeholder="Where should people go to participate?"
                              rows="2"
                              v-model="growthSession.location"/>
                </div>

                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-gray-700">Time:</h3>
                    <div>
                        <label class="block text-slate-700 text-sm font-bold mb-2" for="date">
                            Date
                        </label>
                        <div :class="{'error-outline': getError('date')}"
                             class="border p-1 border-slate-400 justify-center">
                            <input id="date" v-model="growthSession.date" type="date">
                        </div>

                        <label class="block text-slate-700 text-sm font-bold mb-2" for="start_time">
                            Start
                        </label>
                        <vue-timepicker :class="{'error-outline': getError('start_time')}"
                                        :minute-interval="15"
                                        advanced-keyboard
                                        auto-scroll
                                        format="hh:mm a"
                                        hide-disabled-items
                                        id="start_time"
                                        v-model="growthSession.start_time"/>
                    </div>
                    <div>
                        <label class="block text-slate-700 text-sm font-bold mb-2" for="end_time">
                            End
                        </label>
                        <vue-timepicker :class="{'error-outline': getError('end_time')}"
                                        :minute-interval="15"
                                        advanced-keyboard
                                        auto-scroll
                                        format="hh:mm a"
                                        hide-disabled-items
                                        id="end_time"
                                        v-model="growthSession.end_time"/>
                    </div>
                </div>

                <h3 class="text-2xl font-sans font-light mb-3 text-gray-700">Attendees</h3>
                <ul>
                    <li class="flex items-center ml-6 my-4" v-for="attendee in growthSession.attendees">
                        <v-avatar :alt="`${attendee.name}'s Avatar`" :size="12" :src="attendee.avatar" class="mr-3"/>
                        {{ attendee.name }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.error-outline {
    outline: red solid 2px;
}
</style>
