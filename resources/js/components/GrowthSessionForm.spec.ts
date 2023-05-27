import {mount, Wrapper} from "@vue/test-utils"
import GrowthSessionForm from "./GrowthSessionForm.vue"
import {IAnyDesk, IStoreGrowthSessionRequest, IUser} from "../types"
import flushPromises from "flush-promises"
import {GrowthSessionApi} from "../services/GrowthSessionApi"
import {IDiscordChannel} from "../types/IDiscordChannel"
import {DiscordChannelApi} from "../services/DiscordChannelApi"
import growthSessionWithCommentsJson from "../../../tests/fixtures/GrowthSessionWithComments.json"
import {AnydesksApi} from "../services/AnydesksApi"
import {DateTime} from "../classes/DateTime"
import {vi} from "vitest"

const user: IUser = {
    avatar: 'lastAirBender.jpg',
    github_nickname: 'jackjack',
    id: 987,
    name: 'Jack Bauer',
    is_vehikl_member: true,
};
const discordChannels: Array<IDiscordChannel> = [
    {
        name: 'Chat One',
        id: '1234567890',
    },
    {
        name: 'Chat Two',
        id: '1234567891',
    }
];
const anyDesks: Array<IAnyDesk> = [
    {
        id: 1,
        name: 'AnyDesk One',
        remote_desk_id: '123 321 423',
    },
    {
        id: 2,
        name: 'AnyDesk Two',
        remote_desk_id: '123 321 424'
    }
];
const startDate: string = "2020-06-25";


describe('GrowthSessionForm', () => {
    let wrapper: Wrapper<GrowthSessionForm>;

    beforeEach(() => {
        GrowthSessionApi.store = vi.fn().mockImplementation(growthSession => growthSession)
        GrowthSessionApi.update = vi.fn().mockImplementation(growthSession => growthSession)
        DiscordChannelApi.index = vi.fn().mockImplementation(() => discordChannels)
        AnydesksApi.getAllAnyDesks = vi.fn().mockImplementation(() => anyDesks)
        wrapper = mount(GrowthSessionForm, {propsData: {owner: user, startDate}})
    });

    const baseGrowthSessionRequest: IStoreGrowthSessionRequest = {
        location: "The growth session location",
        topic: "Chosen topic",
        title: "Chosen title",
        date: "2020-10-01",
        start_time: "04:45 pm",
        discord_channel_id: null,
        anydesk_id: null,
        allow_watchers: true
    }
    describe('used for creation', () => {
        describe('allows a growth session to be created', () => {
            type TGrowthSessionCreationScenario = [string, IStoreGrowthSessionRequest]

            const scenarios: TGrowthSessionCreationScenario[] = [
                [
                    "Can accept no limit and no end time",
                    {...baseGrowthSessionRequest, attendee_limit: undefined, end_time: undefined}
                ],
                [
                    "Can accept a limit",
                    {
                        ...baseGrowthSessionRequest,
                        attendee_limit: 4
                    }
                ],
                [
                    "Can accept an end time",
                    {
                        ...baseGrowthSessionRequest,
                        end_time: "5:45 pm"
                    }
                ],
                [
                    "Can accept no Discord channel",
                    {...baseGrowthSessionRequest, discord_channel_id: null}
                ],
                [
                    "Can accept a Discord channel",
                    {...baseGrowthSessionRequest, discord_channel_id: "1234567890"}
                ],
                [
                    "Can set the growth session to allow watchers",
                    {...baseGrowthSessionRequest, allow_watchers: true}
                ],
                [
                    "Can set the growth session to not allow watchers",
                    {...baseGrowthSessionRequest, allow_watchers: false}
                ],
                [
                    "Can accept no AnyDesk",
                    {...baseGrowthSessionRequest, anydesk_id: null}
                ],
                [
                    "Can accept an AnyDesk",
                    {...baseGrowthSessionRequest, anydesk_id: 1}
                ]
            ]

            it.each(scenarios)('%s', async (testTitle, payload) => {
                const {
                    title: chosenTitle,
                    topic: chosenTopic,
                    location: chosenLocation,
                    start_time: chosenStartTime,
                    attendee_limit: chosenLimit,
                    discord_channel_id: discordChannelId,
                    anydesk_id: anyDeskId,
                    allow_watchers,

                } = payload;

                const discordChannel = discordChannels.filter(channel => channel.id === discordChannelId)[0] || undefined;
                const anyDesk = anyDesks.filter(desk => desk.id === anyDeskId)[0] || undefined;

                await flushPromises();

                window.confirm = vi.fn()

                wrapper.find("#start-time").setValue(DateTime.parseByTime(chosenStartTime).toTimeString24Hours())
                wrapper.find("#title").setValue(chosenTitle)
                wrapper.find('#topic').setValue(chosenTopic);
                wrapper.find('#location').setValue(chosenLocation);

                if (!chosenLimit) {
                    wrapper.find("#no-limit").trigger("click")
                }

                wrapper.find("#allow-watchers").setChecked(allow_watchers)


                if (chosenLimit) {
                    wrapper.find("#attendee-limit").setValue(chosenLimit)
                }

                if (discordChannel) {
                    wrapper.find("#discord-channel").setValue(discordChannel.id)
                }

                if(anyDesk) {
                    wrapper.find("#anydesks-toggle").setChecked()
                    await wrapper.vm.$nextTick()

                    wrapper.find("#anydesk-selection").setValue(anyDeskId.toString())
                }

                await wrapper.vm.$nextTick();
                wrapper.find('button[type="submit"]').trigger('click');
                await flushPromises();

                const expectedPayload: IStoreGrowthSessionRequest = {
                    title: chosenTitle,
                    attendee_limit: chosenLimit,
                    location: chosenLocation,
                    date: startDate,
                    start_time: chosenStartTime,
                    end_time: '05:00 pm',
                    topic: chosenTopic,
                    discord_channel_id: discordChannelId,
                    anydesk_id: anyDeskId,
                    allow_watchers,
                };

                if (!chosenLimit) {
                    delete expectedPayload.attendee_limit;
                }

                expect(GrowthSessionApi.store).toHaveBeenCalledWith(expect.objectContaining(expectedPayload));
            });
        });

        it('emits submitted event on success', async () => {
            wrapper.find("#start-time").setValue("09:30")
            wrapper.find("#title").setValue("Something")
            wrapper.find('#topic').setValue('Anything');
            wrapper.find('#location').setValue('Anywhere');
            await wrapper.vm.$nextTick();
            wrapper.find('button[type="submit"]').trigger('click');
            await flushPromises();
            expect(wrapper.emitted('submitted')).toBeTruthy();
        });

        it('starts with the submit button disabled', () => {
            expect(wrapper.find("button[type=\"submit\"]").element.disabled).toBeTruthy()
        });

        it('enables the submit button when all required fields are filled', async () => {
            wrapper.find("#start-time").setValue("09:30")
            wrapper.find("#title").setValue("Something")
            wrapper.find('#topic').setValue('Anything');
            wrapper.find('#location').setValue('Anywhere');
            await wrapper.vm.$nextTick()
            expect(wrapper.find("button[type=\"submit\"]").element.disabled).toBeFalsy()
        });

        it('has a no limit checkbox', () => {
            expect(wrapper.find("#no-limit").exists()).toBeTruthy()
        })

        it('starts with allow watchers checkbox enabled', () => {
            expect(wrapper.find("#allow-watchers").element.checked).toBe(true)
        });

        it('hides the attendee limit input, if no limit checkbox is checked', async () => {
            wrapper.find("#no-limit").setChecked(true)
            await wrapper.vm.$nextTick()

            expect(wrapper.findComponent({ref: 'attendee-limit'}).exists()).toBeFalsy();
        })

        it('displays a dropdown select with Discord channel names', async () => {
            await flushPromises()
            expect(DiscordChannelApi.index).toHaveBeenCalled()

            const discordChannelDropdown = wrapper.find("#discord-channel")
            expect(discordChannelDropdown.exists()).toBeTruthy()
            const optionsAvailable = discordChannelDropdown.findAll("option").map((option) => option.text())
            const optionsExpected = discordChannels.map(option => option.name)
            expect(optionsAvailable).toEqual(expect.arrayContaining(optionsExpected))
        })

        it('does not display the dropdown select with Discord channel names if no channels are returned', async () => {
            DiscordChannelApi.index = vi.fn().mockImplementation(() => [])
            wrapper = mount(GrowthSessionForm, {propsData: {owner: user, startDate}})

            await flushPromises()

            expect(wrapper.find("#discord-channel").exists()).toBeFalsy()
        })

        it('displays a checkbox and dropdown select with AnyDesks when AnyDesks given', async () => {
            const useAnyDesksSelector = wrapper.find("#anydesks-toggle")
            await useAnyDesksSelector.setChecked()

            const anyDesksSelector = wrapper.find("#anydesk-selection")
            expect(anyDesksSelector.exists()).toBeTruthy()
            const optionsAvailable = anyDesksSelector.findAll("option").map((option) => option.text())
            const optionsExpected = anyDesks.map(option => option.name)
            expect(optionsAvailable).toEqual(expect.arrayContaining(optionsExpected))
        })

        it('does not display a checkbox and dropdown select with AnyDesks when no AnyDesks given', async () => {
            AnydesksApi.getAllAnyDesks = vi.fn().mockImplementation(() => [])
            wrapper = mount(GrowthSessionForm, {propsData: {owner: user, startDate}})

            await flushPromises()

            const useAnyDesksSelector = wrapper.find("#anydesks-toggle")
            expect(useAnyDesksSelector.exists()).toBe(false)

            const anyDesksSelector = wrapper.find("#anydesk-selection")
            expect(anyDesksSelector.exists()).toBe(false)
        })

        it('autofills the location with the Discord channel when selected', async () => {
            await flushPromises();
            const selector = wrapper.find("#discord-channel")
            const locationInput = wrapper.find("#location").element as HTMLInputElement

            expect(locationInput.value).toBe("")

            await selector.setValue(discordChannels[0].id)
            expect(locationInput.value).toBe(`Discord Channel: ${discordChannels[0].name}`)
        })

        it('changes the location when new Discord channel is selected if old location was a Discord channel', async () => {
            await flushPromises();
            const selector = wrapper.find("#discord-channel")
            const locationInput = wrapper.find("#location").element as HTMLInputElement

            await selector.setValue(discordChannels[0].id)
            expect(locationInput.value).toBe(`Discord Channel: ${discordChannels[0].name}`)

            await selector.setValue(discordChannels[1].id)
            expect(locationInput.value).toBe(`Discord Channel: ${discordChannels[1].name}`)
        })

        it('allows users to create a public growth session', async () => {
            const growthSessionInformation: IStoreGrowthSessionRequest = {
                location: "The growth session location",
                topic: "Chosen topic",
                title: "Chosen title",
                date: startDate,
                start_time: "4:45 pm",
                is_public: true
            }

            await wrapper.find("#title").setValue(growthSessionInformation.title)
            await wrapper.find("#topic").setValue(growthSessionInformation.topic)
            await wrapper.find("#location").setValue(growthSessionInformation.location)
            await wrapper.find("#start-time").setValue(DateTime.parseByTime(growthSessionInformation.start_time).toTimeString12Hours())
            await wrapper.find("#is-public").setChecked(true)

            wrapper.find("button[type=submit]").trigger("click")

            expect(GrowthSessionApi.store).toHaveBeenCalledWith(expect.objectContaining({is_public: true}))
        })
    })

    describe('used for editing', () => {
        beforeEach(() => {
            wrapper = mount(GrowthSessionForm, {
                propsData: {
                    owner: user,
                    startDate,
                    growthSession: growthSessionWithCommentsJson
                }
            });
        });

        it('allows is public field to be editable', () => {
            expect(wrapper.find("#is-public").exists()).toBe(true)
        });
    });

});

