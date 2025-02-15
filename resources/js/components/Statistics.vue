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
  {
    label: "Mobbed",
    field: "has_mobbed_with_count",
    width: "15%",
    sortable: true,
    display: (row: IUserStatistics) => renderParticipationButton(row, 'has_mobbed_with')
  },
  {
    label: "Not Mobbed",
    field: "has_not_mobbed_with_count",
    width: "15%",
    sortable: true,
    display: (row: IUserStatistics) => renderParticipationButton(row, 'has_not_mobbed_with')
  }
];


const startDate = ref<string | null>(null)
const name = ref<string>("")
const allData = ref<IUserStatistics[]>([])
const dialogData = reactive<{ title: string, userNames: string[] }>({
  title: "You have mobbed with",
  userNames: [],
})
const dialogUserNames = ref<string[]>([])

const table = reactive({
  isLoading: true,
  columns,
  rows: computed(() => allData.value.filter(row => row.name.toLowerCase().includes(name.value.toLowerCase()))
  ),
  sortable: {
    order: "name",
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
  const dialog = document.getElementById('participation-names') as HTMLDialogElement;
  const userId = Number(this.getAttribute('data-id'));
  const key = this.getAttribute('data-payload') as 'has_mobbed_with' | 'has_not_mobbed_with';

  dialogData.title = key === 'has_mobbed_with' ? 'You have mobbed with: ' : 'You have not mobbed with: ';
  dialogData.userNames = table.rows.find(row => row.user_id === userId)?.[key]?.map((member) => member.name) ?? [];
  dialog.showModal();
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


function renderParticipationButton(row: IUserStatistics,
                                   otherUsersKey: has_not_mobbed_with | has_mobbed_with) {
  const otherUserCountKey = `${otherUsersKey}_count`;

  if (row[otherUserCountKey] === 0) {
    return (`
    <div class="flex justify-center">
        ${otherUsersKey === 'has_mobbed_with' ? '😔' : '🎉'}
    </div>
    `);
  }

  return (
      `
      <div class="flex justify-center">
         <abbr title="See names" class="whitespace-nowrap">
            <button data-id="${row.user_id}"
              data-payload="${otherUsersKey}"
              data-type="alert-button"
              class="is-rows-el quick-btn md:w-1/2 hover:brightness-75 hover:font-bold underline">
                  ${row[otherUserCountKey]}
            </button>
          </abbr>
      </div>
      `
  );
}
</script>

<template>
  <div class="mt-6 mx-auto max-w-[115rem]">
    <dialog id="participation-names" class="w-full max-w-xl p-0 overflow-auto px-4 py-8">
      <h2 class="mb-8 text-3xl bg-white text-center tracking-wide text-slate-600 font-bold p-2 pt-4 pb-1 sticky sm:relative top-0 w-full z-20 rounded-t-xl"
          v-text="dialogData.title"/>
      <ul class="grid grid-cols-3">
        <li v-for="(name, index) in dialogData.userNames" :key="index" v-text="name"/>
      </ul>

      <form class="mt-8" method="dialog">
        <button
            class="border-gray-600 hover:bg-gray-600 focus:bg-gray-700 focus:text-white text-gray-600 border-4 bg-white hover:text-white font-bold py-2 px-4 w-full join-button">
          OK
        </button>
      </form>
    </dialog>

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
