import "./bootstrap";
import Vue from 'vue';
import WeekView from "./components/WeekView.vue";
import MobView from "./components/MobView.vue";
import MobEdit from "./components/MobEdit.vue";
import VModal from 'vue-js-modal';
import moment from 'moment';
Vue.use(VModal);
new Vue({
    el: '#app',
    components: {WeekView, MobView, MobEdit}
});


window.moment = moment;
