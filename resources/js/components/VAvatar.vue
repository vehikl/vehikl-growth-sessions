<template>
    <div :class="`w-${size} h-${size} relative`">
        <div class="group w-full h-full rounded-full overflow-hidden shadow-inner">
            <img :src="src" :alt="alt"
                 class="object-cover object-center w-full h-full visible group-hover:grayscale"/>
        </div>
    </div>
</template>

<script lang="ts">
import {Component, Prop, Vue} from "vue-property-decorator"

@Component
    export default class VAvatar extends Vue {
        @Prop({default: 20}) readonly size!: string;
        @Prop({required: true}) readonly src!: string;
        @Prop({required: true}) readonly alt!: string;
    }
</script>
