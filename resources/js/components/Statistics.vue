<script lang="ts" setup>
import TableLite from "vue3-table-lite/ts";
import { computed, onBeforeMount, reactive, ref, watch } from "vue";
import axios from "axios";
import { IStatistics, IUserStatistics } from "../types";
import { DateTime } from "../classes/DateTime";

type ColumnType = {
    label: string;
    field: keyof IUserStatistics;
    width: string;
    sortable: boolean;
    isKey?: boolean;
    display?: (row: IUserStatistics) => void;
};

type Settings = {
    list: string[],
    shouldUseList: boolean;
    startDate?: string;
    endDate?: string;
}

const fullDisplay = location.search.includes("full-display");

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
    },
];

const extraColumns = fullDisplay ? participationCountColumns : [];

const columns: ColumnType[] = [
    { label: "ID", field: "user_id", width: "3%", sortable: true, isKey: true },
    { label: "Name", field: "name", width: "10%", sortable: true },
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

const FIRST_DAY = "2020-05-21";
const startDate = ref<string | null>(new DateTime(FIRST_DAY).toDateString());
const endDate = ref<string | null>(new DateTime().toDateString());

const FILTER_STORAGE_KEY = "statistics_filter";

const serializeSettings = () => {
    const settings: Settings = {
        list: filter.list,
        shouldUseList: filter.shouldUseList,
        startDate: startDate.value,
        endDate: endDate.value,
    };

    return btoa(JSON.stringify(settings));
};

const deserializeSettings = (settingsStr: string): Settings => {
    try {
        return JSON.parse(atob(settingsStr));
    } catch (e) {
        console.error("Error parsing settings from URL:", e);
        return getDefaultSettings();
    }
};

const generateShareableUrl = () => {
    const url = new URL(window.location.href);
    url.searchParams.set("settings", serializeSettings());

    if (fullDisplay && !url.searchParams.has("full-display")) {
        url.searchParams.set("full-display", "");
    }

    return url.toString();
};

const shareUrl = () => {
    const url = generateShareableUrl();
    navigator.clipboard
        .writeText(url)
        .then(() => {
            alert("URL copied to clipboard!");
        })
        .catch((err) => {
            console.error("Could not copy URL: ", err);
        });
};

function getDefaultSettings() {
    return {
        list: [],
        shouldUseList: false,
        startDate: new DateTime(FIRST_DAY).toDateString(),
        endDate: new DateTime().toDateString()
    };
}

function getSettingsFromLocalStorage(): Settings {
    try {
        const storedSettings = localStorage.getItem(FILTER_STORAGE_KEY);
        if (storedSettings) {
            return JSON.parse(storedSettings);
        }
    } catch (e) {
        console.error("Error loading filter settings from localStorage:", e);
    }

    return getDefaultSettings();
}

const getFilterSettings = (): Settings => {
    const urlParams = new URLSearchParams(window.location.search);
    const settingsParam = urlParams.get("settings");

    if (settingsParam) {
        try {
            const settings = deserializeSettings(settingsParam);

            if (settings.startDate) {
                startDate.value = settings.startDate;
            }
            if (settings.endDate) {
                endDate.value = settings.endDate;
            }

            return settings;
        } catch (e) {
            console.error("Error loading settings from URL:", e);
        }
    }

    return getSettingsFromLocalStorage();
};

const initialSettings = getFilterSettings();
const filter = reactive({
    name: "",
    list: initialSettings.list,
    shouldUseList: initialSettings.shouldUseList,
});

const saveFilterSettings = () => {
    try {
        const settings: Settings = {
            list: filter.list,
            shouldUseList: filter.shouldUseList,
        };
        localStorage.setItem(FILTER_STORAGE_KEY, JSON.stringify(settings));
    } catch (e) {
        console.error("Error saving filter settings to localStorage:", e);
    }
};

watch([() => filter.list, () => filter.shouldUseList], saveFilterSettings, {
    deep: true,
});

const rows = computed(() => {
    if (!filter.shouldUseList || filter.list.length === 0) {
        return allData.value.filter((row) =>
            row.name.toLowerCase().includes(filter.name.toLowerCase())
        );
    }

    return allData.value
        .filter((row) =>
            filter.list.some((nameToFilterFor) =>
                row.name.toLowerCase().includes(nameToFilterFor.toLowerCase())
            )
        )
        .filter((row) =>
            row.name.toLowerCase().includes(filter.name.toLowerCase())
        );
});

const allData = ref<IUserStatistics[]>([]);
const dialogData = reactive<{ title: string; userNames: string[] }>({
    title: "You have mobbed with",
    userNames: [],
});

const addNameToFilter = () => {
    if (filter.name.trim() && !filter.list.includes(filter.name.trim())) {
        filter.list.push(filter.name.trim());
        filter.name = "";
    }
};

const removeNameFromFilter = (nameToRemove: string) => {
    filter.list = filter.list.filter((name) => name !== nameToRemove);
};

const clearFilterList = () => {
    filter.list = [];
};

const table = reactive({
    isLoading: true,
    columns,
    rows,
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

function displayAlertHandler(this: HTMLElement, event: Event) {
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

function tableLoadingFinish(elements: HTMLElement[]) {
    table.isLoading = false;
    Array.prototype.forEach.call(elements, function (element: HTMLElement) {
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

function setStartDateAsFirstDay() {
    startDate.value = new DateTime(FIRST_DAY).toDateString();
}

function setEndDateAsToday() {
    endDate.value = new DateTime().toDateString();
}

function setThisWeekDateRange() {
    setEndDateAsToday();
    setStartDateToLastMonday();
}

function setThisMonthDateRange() {
    setEndDateAsToday();

    // Get the first day of the current month
    const today = new DateTime();
    const firstDayOfMonth = new DateTime(today.format("YYYY-MM-01"));

    // Calculate the first Monday of the month
    const firstDayWeekday = firstDayOfMonth.weekDayNumber();
    const daysToAdd = firstDayWeekday === 1 ? 0 : (8 - firstDayWeekday) % 7;
    const firstMondayOfMonth = firstDayOfMonth.addDays(daysToAdd);

    // If the first Monday is after today, go back to the previous month's last Monday
    if (
        daysToAdd > 0 &&
        firstMondayOfMonth.toDateString() > today.toDateString()
    ) {
        setStartDateToLastMonday();
    } else {
        startDate.value = firstMondayOfMonth.toDateString();
    }
}

function setAllTimeDateRange() {
    setEndDateAsToday();
    setStartDateAsFirstDay();
}

function renderParticipationButton(
    row: IUserStatistics,
    otherUsersKey: "has_not_mobbed_with" | "has_mobbed_with"
): string {
    const otherUserCountKey:
        | "has_not_mobbed_with_count"
        | "has_mobbed_with_count" = `${otherUsersKey}_count`;

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

        <fieldset title="Filters">
            <section
                class="flex items-start gap-48"
                aria-description="Filter by Name and Date"
            >
                <label class="flex gap-4 my-4 text-sm items-center font-bold">
                    Name
                    <input
                        v-model="filter.name"
                        @keydown.enter.prevent="addNameToFilter"
                        class="max-w-xs border px-2 text-base font-light"
                        name="filter-by-name"
                        type="text"
                    />
                    <button
                        @click="addNameToFilter"
                        class="border border-gray-700 bg-gray-50 text-gray-900 px-4 hover:brightness-90 hover:font-bold"
                    >
                        Apply
                    </button>
                </label>

                <section aria-description="date selection" v-if="fullDisplay">
                    <div class="flex gap-4">
                        <div class="relative">
                            <button
                                class="text-xs absolute right-0 text-blue-500"
                                @click="setStartDateAsFirstDay"
                            >
                                First Day
                            </button>
                            <label
                                class="flex gap-4 my-4 text-sm items-center font-bold"
                            >
                                Start Date
                                <input
                                    v-model="startDate"
                                    class="max-w-xs border px-2 text-base font-light"
                                    type="date"
                                    name="start-date"
                                />
                            </label>
                        </div>

                        <div class="relative" v-if="fullDisplay">
                            <button
                                class="text-xs absolute right-0 text-blue-500"
                                @click="setEndDateAsToday"
                            >
                                Today
                            </button>
                            <label
                                class="flex gap-4 my-4 text-sm items-center font-bold"
                            >
                                End Date
                                <input
                                    v-model="endDate"
                                    class="max-w-xs border px-2 text-base font-light"
                                    type="date"
                                    name="end-date"
                                />
                            </label>
                        </div>
                    </div>

                    <div class="flex items-end gap-2 mb-4">
                        <button
                            class="text-xs border border-gray-700 bg-gray-50 text-gray-900 px-4 hover:brightness-90 hover:font-bold"
                            @click="setThisWeekDateRange"
                        >
                            This Week
                        </button>
                        <button
                            class="text-xs border border-gray-700 bg-gray-50 text-gray-900 px-4 hover:brightness-90 hover:font-bold"
                            @click="setThisMonthDateRange"
                        >
                            This Month
                        </button>
                        <button
                            class="text-xs border border-gray-700 bg-gray-50 text-gray-900 px-4 hover:brightness-90 hover:font-bold"
                            @click="setAllTimeDateRange"
                        >
                            All Time
                        </button>
                    </div>
                </section>
            </section>

            <section
                aria-description="Filter by list"
                class="my-4"
                v-if="fullDisplay"
            >
                <div class="flex gap-4">
                    <label>
                        Apply List to Filter
                        <input type="checkbox" v-model="filter.shouldUseList" />
                    </label>

                    <button
                        class="text-xs border border-gray-700 bg-gray-50 text-gray-900 px-4 hover:brightness-90 hover:font-bold"
                        @click="clearFilterList"
                    >
                        Clear
                    </button>

                    <button
                        class="text-xs border border-gray-700 bg-gray-50 text-gray-900 px-4 hover:brightness-90 hover:font-bold"
                        @click="shareUrl"
                        title="Copy shareable URL to clipboard"
                    >
                        Share URL
                    </button>
                </div>

                <ul class="mt-2 border border-gray-200 p-2 w-full">
                    <li
                        v-if="filter.list.length === 0"
                        class="opacity-25 text-center"
                    >
                        -- Empty --
                    </li>
                    <li
                        v-for="(selectedName, index) in filter.list"
                        :key="index"
                        class="inline-flex min-w-[5rem] mx-4 flex-wrap items-center mb-1 border-r"
                    >
                        <span>{{ selectedName }}</span>
                        <button
                            @click="removeNameFromFilter(selectedName)"
                            class="text-red-500 hover:text-red-700 ml-2"
                        >
                            ×
                        </button>
                    </li>
                </ul>
            </section>
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
