import "./bootstrap";
import Vue from 'vue';
import WeekView from "./components/WeekView.vue";
import GrowthSessionView from "./components/GrowthSessionView.vue";
import VAvatar from "./components/VAvatar.vue";
import VModal from 'vue-js-modal';
import vSelect from 'vue-select';
import GrowthSessionCardNew from "./components/GrowthSessionCardNew.vue";

Vue.use(VModal);
Vue.component('v-select', vSelect)

new Vue({
    el: '#app',
    components: {WeekView, GrowthSessionView, VAvatar, GrowthSessionCardNew}
});
