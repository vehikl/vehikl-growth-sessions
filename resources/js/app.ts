import "./bootstrap";
import Vue from 'vue';
import WeekView from "./components/WeekView.vue";
import VModal from 'vue-js-modal';

Vue.use(VModal);
new Vue({
    el: '#app',
    components: {WeekView}
});
