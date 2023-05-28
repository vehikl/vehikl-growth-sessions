import "./bootstrap"
import {createApp} from "vue"
import WeekView from "./components/WeekView.vue"
import GrowthSessionView from "./components/GrowthSessionView.vue"
import VAvatar from "./components/VAvatar.vue"

createApp({
    //@ts-ignore
    components: {WeekView, GrowthSessionView, VAvatar}
}).mount("#app")
