<script lang="ts" setup>
import TableLite from "vue3-table-lite/ts";
import {computed, onBeforeMount, reactive, ref, watch} from "vue";
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
  {label: "Mobbed", field: "has_mobbed_with_count", width: "15%", sortable: true},
  {
    label: "Not Mobbed",
    field: "has_not_mobbed_with_count",
    width: "15%",
    sortable: true,
    display: renderNotMobbedButton
  }
];


const startDate = ref<string | null>(null)
const name = ref<string>("")
const allData = ref<IUserStatistics[]>([])

const table = reactive({
  isLoading: true,
  columns,
  rows: computed(() => allData.value.filter(row => row.name.toLowerCase().includes(name.value.toLowerCase()))
  ),
  sortable: {
    order: "user_id",
    sort: "asc",
  },
  totalRecordCount: computed(() => table.rows.length)
});

const apiQuery = computed<string>(() => {
  const query = new URLSearchParams();
  if (startDate.value) {
    query.set('start_date', startDate.value);
  }

  return query.toString();
})

onBeforeMount(async () => {
  await fetchStatistics();
})

watch([startDate], fetchStatistics);

async function fetchStatistics() {
  table.isLoading = true;
  let url = '/statistics';
  if (apiQuery.value.length > 0) {
    url += `?${apiQuery.value}`
  }

  const response = await axios.get<IStatistics>(url);
  allData.value = response.data.users;
  table.isLoading = false;
}

function displayAlertHandler(event: Event) {
  event.stopPropagation();
  alert(this.getAttribute('data-payload'));
}

function tableLoadingFinish(elements) {
  table.isLoading = false;
  Array.prototype.forEach.call(elements, function (element) {
    if (element.getAttribute('data-type') === 'alert-button') {

      element.removeEventListener("click", displayAlertHandler);
      element.addEventListener("click", displayAlertHandler);
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
  <div class="mt-6 mx-auto max-w-[115rem]">
    <fieldset class="flex gap-8" title="Filters">
      <label class="flex gap-4 my-4 text-sm items-center font-bold">
        Name
        <input v-model="name" class="max-w-xs border px-2 text-base font-light" name="filter-by-name" type="text">
      </label>

      <label v-show="false" class="flex gap-4 my-4 text-sm items-center font-bold">
        Start Date
        <input v-model="startDate" class="max-w-xs border px-2 text-base font-light" type="date">
      </label>
    </fieldset>

    <table-lite
        :columns="table.columns"
        :is-loading="table.isLoading"
        :is-static-mode="true"
        :page-size="25"
        :rows="table.rows"
        :sortable="table.sortable"
        :total="table.totalRecordCount"
        @is-finished="tableLoadingFinish"
    />
  </div>

</template>

<style lang="scss" scoped>

</style>