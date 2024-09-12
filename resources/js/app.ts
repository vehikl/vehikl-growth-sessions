import "./bootstrap"
import {createApp} from "vue"
import WeekView from "./components/WeekView.vue"
import GrowthSessionView from "./components/GrowthSessionView.vue"
import Statistics from "./components/Statistics.vue"
import VAvatar from "./components/VAvatar.vue"
import moment from "moment-timezone"

moment.tz.setDefault("America/Toronto");

createApp({
    components: {WeekView, GrowthSessionView, Statistics, VAvatar}
}).mount("#app")
