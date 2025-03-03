<script lang="ts" setup>
import TableLite from "vue3-table-lite/ts";
import {computed, onBeforeMount, reactive, ref, watch} from "vue";
import axios from "axios";
import {IStatistics, IUserStatistics} from "../types";
import {DateTime} from "../classes/DateTime";

type ColumnType = {
    label: string;
    field: keyof IUserStatistics;
    width: string;
    sortable: boolean;
    isKey?: boolean;
    display?: (row: IUserStatistics) => void;
};

const fullDisplay = location.search.includes('full-display');

const participationCountColumns: ColumnType[] = [
    {
        label: "# Hosted",
        field: "sessions_hosted_count",
        width: "15%",
        sortable: true,
    },
    {
        label: "# Attended",
        field: "sessions_attended_count",
        width: "15%",
        sortable: true,
    },
    {
        label: "# Watched",
        field: "sessions_watched_count",
        width: "15%",
        sortable: true,
    },
    {
        label: "Total",
        field: "total_sessions_count",
        width: "15%",
        sortable: true,
    }
];

const extraColumns = fullDisplay ? participationCountColumns : [];

const columns: ColumnType[] = [
    {label: "ID", field: "user_id", width: "3%", sortable: true, isKey: true},
    {label: "Name", field: "name", width: "10%", sortable: true},
    {
        label: "Yet to Mob With",
        field: "has_not_mobbed_with_count",
        width: "15%",
        sortable: true,
        display: (row: IUserStatistics) =>
            renderParticipationButton(row, "has_not_mobbed_with"),
    },
    ...extraColumns,
];

const startDate = ref<string | null>(new DateTime("2020-05-21").toDateString());
const endDate = ref<string | null>(new DateTime().toDateString());
const name = ref<string>("");
const allData = ref<IUserStatistics[]>([]);
const dialogData = reactive<{ title: string; userNames: string[] }>({
    title: "You have mobbed with",
    userNames: [],
});

const table = reactive({
    isLoading: true,
    columns,
    rows: computed(() =>
        allData.value.filter((row) =>
            row.name.toLowerCase().includes(name.value.toLowerCase())
        )
    ),
    sortable: {
        order: "name",
        sort: "asc",
    },
    totalRecordCount: computed(() => table.rows.length),
});

const apiQuery = computed<string>(() => {
    const query = new URLSearchParams();
    if (startDate.value) {
        query.set("start_date", startDate.value);
    }

    if (endDate.value) {
        query.set("end_date", endDate.value);
    }

    return query.toString();
});

onBeforeMount(async () => {
    await fetchStatistics();
});

watch([startDate, endDate], fetchStatistics);

async function fetchStatistics() {
    table.isLoading = true;
    let url = "/statistics";
    if (apiQuery.value.length > 0) {
        url += `?${apiQuery.value}`;
    }

    const response = await axios.get<IStatistics>(url);
    allData.value = response.data.users;
    table.isLoading = false;
}

function displayAlertHandler(event: Event) {
    event.stopPropagation();
    const dialog = document.getElementById(
        "participation-names"
    ) as HTMLDialogElement;
    const userId = Number(this.getAttribute("data-id"));
    const key = this.getAttribute("data-payload") as
        | "has_mobbed_with"
        | "has_not_mobbed_with";

    dialogData.title =
        key === "has_mobbed_with"
            ? "You have mobbed with: "
            : "You have not mobbed with: ";
    dialogData.userNames =
        table.rows
            .find((row) => row.user_id === userId)
            ?.[key]?.map((member) => member.name) ?? [];
    dialog.showModal();
}

function tableLoadingFinish(elements) {
    table.isLoading = false;
    Array.prototype.forEach.call(elements, function (element) {
        if (element.getAttribute("data-type") === "alert-button") {
            element.removeEventListener("click", displayAlertHandler);
            element.addEventListener("click", displayAlertHandler);
        }
    });
}

function setStartDateToLastMonday() {
    if (!endDate.value) return;

    const endingWeekday = new DateTime(endDate.value).weekDayNumber();
    const daysToSubtract = endingWeekday === 0 ? 6 : endingWeekday - 1;

    const lastMonday = new DateTime(endDate.value).addDays(-daysToSubtract);
    startDate.value = lastMonday.toDateString();
}

function setEndDateAsToday() {
    endDate.value = new DateTime().toDateString();
}

function renderParticipationButton(
    row: IUserStatistics,
    otherUsersKey: 'has_not_mobbed_with' | 'has_mobbed_with'
) {
    const otherUserCountKey: 'has_not_mobbed_with_count' | 'has_mobbed_with_count' = `${otherUsersKey}_count`;

    if (row[otherUserCountKey] === 0) {
        return `
    <div class="flex justify-center">
        ${otherUsersKey === "has_mobbed_with" ? "😔" : "🎉"}
    </div>
    `;
    }

    return `
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
      `;
}
</script>

<template>
    <div class="mt-6 mx-auto lg:mx-6 max-w-[115rem]">
        <dialog
            id="participation-names"
            class="w-full max-w-xl p-0 overflow-auto px-4 py-8"
        >
            <h2
                class="mb-8 text-3xl bg-white text-center tracking-wide text-slate-600 font-bold p-2 pt-4 pb-1 sticky sm:relative top-0 w-full z-20 rounded-t-xl"
                v-text="dialogData.title"
            />
            <ul class="grid grid-cols-3">
                <li
                    v-for="(name, index) in dialogData.userNames"
                    :key="index"
                    v-text="name"
                />
            </ul>

            <form class="mt-8" method="dialog">
                <button
                    class="border-gray-600 hover:bg-gray-600 focus:bg-gray-700 focus:text-white text-gray-600 border-4 bg-white hover:text-white font-bold py-2 px-4 w-full join-button"
                >
                    OK
                </button>
            </form>
        </dialog>

        <fieldset class="flex gap-8" title="Filters">
            <label class="flex gap-4 my-4 text-sm items-center font-bold">
                Name
                <input
                    v-model="name"
                    class="max-w-xs border px-2 text-base font-light"
                    name="filter-by-name"
                    type="text"
                />
            </label>

            <div class="relative">
                <button
                    class="text-xs absolute right-0 text-blue-500 last-monday-btn"
                    @click="setStartDateToLastMonday"
                >
                    Prior Monday
                </button>
                <label class="flex gap-4 my-4 text-sm items-center font-bold">
                    Start Date
                    <input
                        v-model="startDate"
                        class="max-w-xs border px-2 text-base font-light"
                        type="date"
                        name="start-date"
                    />
                </label>
            </div>

            <div class="relative">
                <button
                    class="text-xs absolute right-0 text-blue-500 last-monday-btn"
                    @click="setEndDateAsToday"
                >
                    Today
                </button>
                <label class="flex gap-4 my-4 text-sm items-center font-bold">
                    End Date
                    <input
                        v-model="endDate"
                        class="max-w-xs border px-2 text-base font-light"
                        type="date"
                        name="end-date"
                    />
                </label>
            </div>

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
