import "./bootstrap";
import Vue from 'vue';
import WeekView from "./components/WeekView.vue";
import MobView from "./components/MobView.vue";
import VModal from 'vue-js-modal';
import moment from 'moment';
Vue.use(VModal);
new Vue({
    el: '#app',
    components: {WeekView, MobView}
});


window.moment = moment;
