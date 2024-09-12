<script lang="ts" setup>
import TableLite from "vue3-table-lite/ts";
import {onBeforeMount, reactive} from "vue";
import axios from "axios";

const table = reactive({
  isLoading: false,
  columns: [
    {label: "ID", field: "user_id", width: "3%", sortable: true, isKey: true},
    {label: "Name", field: "name", width: "10%", sortable: true},
    {label: "Total Participation", field: "total_sessions_count", width: "15%", sortable: true},
    {label: "Sessions Hosted", field: "sessions_hosted_count", width: "15%", sortable: true},
    {label: "Sessions Attended", field: "sessions_attended_count", width: "15%", sortable: true},
    {label: "Sessions Watched", field: "sessions_watched_count", width: "15%", sortable: true},
    {label: "Mobbed", field: "has_mobbed_with_count", width: "15%", sortable: true},
    {label: "Not Mobbed", field: "has_not_mobbed_with_count", width: "15%", sortable: true},
  ],
  rows: [],
  totalRecordCount: 0,
  sortable: {
    order: "user_id",
    sort: "asc",
  },
});

onBeforeMount(async () => {
  const response = await axios.get('/statistics');
  table.rows = response.data.users;
  table.totalRecordCount = response.data?.users?.length ?? 0;
})
</script>

<template>
  <table-lite
      :columns="table.columns"
      :is-loading="table.isLoading"
      :is-static-mode="true"
      :rows="table.rows"
      :sortable="table.sortable"
      :total="table.totalRecordCount"
      class="mt-6 mx-auto max-w-[115rem]"
      @is-finished="table.isLoading = false"
  />
</template>

<style lang="scss" scoped>

</style>