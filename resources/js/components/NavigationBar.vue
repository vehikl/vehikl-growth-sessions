<template>

</template>

<script lang="ts">
    import {Vue, Component} from 'vue-property-decorator';

    @Component
    export default class NavigationBar extends Vue {
        isExpanded: boolean = false;

        created() {
            this.onWindowsizeChanged();
            window.addEventListener('resize', this.onWindowsizeChanged);
        }

        beforeDestroy() {
            window.removeEventListener('resize', this.onWindowsizeChanged);
        }

        onWindowsizeChanged() {
            this.isExpanded = window.innerWidth >= 1024;
        }
    }
</script>
