import "./bootstrap";
import Vue from 'vue';
import WeekView from "./components/WeekView.vue";
import GrowthSessionView from "./components/GrowthSessionView.vue";
import GrowthSessionEdit from "./components/GrowthSessionEdit.vue";
import VAvatar from "./components/VAvatar.vue";
import VModal from 'vue-js-modal';

Vue.use(VModal);

new Vue({
    el: '#app',
    components: {WeekView, GrowthSessionView, GrowthSessionEdit, VAvatar}
});
