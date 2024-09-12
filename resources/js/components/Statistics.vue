<script lang="ts" setup>
import TableLite from "vue3-table-lite/ts";
import {onBeforeMount, reactive} from "vue";
import axios from "axios";
import {IStatistics, IUserStatistics} from "../types";

type ColumnType = {
  label: string,
  field: keyof IUserStatistics,
  width: string,
  sortable: string,
  isKey?: boolean
};

const columns: ColumnType[] = [
  {label: "ID", field: "user_id", width: "3%", sortable: true, isKey: true},
  {label: "Name", field: "name", width: "10%", sortable: true},
  {label: "Total Participation", field: "total_sessions_count", width: "15%", sortable: true},
  {label: "Sessions Hosted", field: "sessions_hosted_count", width: "15%", sortable: true},
  {label: "Sessions Attended", field: "sessions_attended_count", width: "15%", sortable: true},
  {label: "Sessions Watched", field: "sessions_watched_count", width: "15%", sortable: true},
  {label: "Mobbed", field: "has_mobbed_with_count", width: "15%", sortable: true},
  {
    label: "Not Mobbed",
    field: "has_not_mobbed_with_count",
    width: "15%",
    sortable: true,
    display: renderNotMobbedButton
  }
];

const table = reactive({
  isLoading: true,
  columns,
  rows: [],
  totalRecordCount: 0,
  sortable: {
    order: "user_id",
    sort: "asc",
  },
});

onBeforeMount(async () => {
  const response = await axios.get<IStatistics>('/statistics');
  table.rows = response.data.users;
  table.totalRecordCount = response.data?.users?.length ?? 0;
})

function tableLoadingFinish(elements) {
  table.isLoading = false;
  Array.prototype.forEach.call(elements, function (element) {
    if (element.getAttribute('data-type') === 'alert-button') {
      element.addEventListener("click", function (event) {
        event.stopPropagation();
        alert(this.getAttribute('data-payload'));
      });
    }
  });
}

function renderNotMobbedButton(row: IUserStatistics) {
  if (row.has_not_mobbed_with_count === 0) {
    return (`
    <div class="flex justify-center">
        🎉
    </div>
    `);
  }

  return (
      `
      <div class="flex justify-center">
         <button data-id="${row.user_id}"
                data-payload="${row.has_not_mobbed_with.map(user => user.name).join(', ')}"
                data-type="alert-button"
                class="is-rows-el quick-btn border border-blue-800 bg-blue-100 md:w-1/2 hover:brightness-75">
          ${row.has_not_mobbed_with_count}
        </button>
      </div>
      `
  );
}
</script>

<template>
  <table-lite
      class="mt-6 mx-auto max-w-[115rem]"
      :columns="table.columns"
      :is-loading="table.isLoading"
      :is-static-mode="true"
      :rows="table.rows"
      :sortable="table.sortable"
      :total="table.totalRecordCount"
      :page-size="25"
      @is-finished="tableLoadingFinish"
  />
</template>

<style lang="scss" scoped>

</style>