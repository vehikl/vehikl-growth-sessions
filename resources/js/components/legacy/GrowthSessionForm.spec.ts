import growthSessionWithCommentsJson from '@/../../tests/fixtures/GrowthSessionWithComments.json';
import { DateTime } from '@/classes/DateTime';
import GrowthSessionForm from '@/components/legacy/GrowthSessionForm.vue';
import { AnydesksApi } from '@/services/AnydesksApi';
import { DiscordChannelApi } from '@/services/DiscordChannelApi';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { TagsApi } from '@/services/TagsApi';
import { IAnyDesk, IStoreGrowthSessionRequest, IUser } from '@/types';
import { IDiscordChannel } from '@/types/IDiscordChannel';
import { mount } from '@vue/test-utils';
import flushPromises from 'flush-promises';
import { vi } from 'vitest';

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
    },
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
        remote_desk_id: '123 321 424',
    },
];
const startDate: string = '2020-06-25';

describe('GrowthSessionForm', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        HTMLDialogElement.prototype.showModal = vi.fn();
        HTMLDialogElement.prototype.close = vi.fn();
        GrowthSessionApi.store = vi.fn().mockImplementation((growthSession) => growthSession);
        GrowthSessionApi.update = vi.fn().mockImplementation((growthSession) => growthSession);
        DiscordChannelApi.index = vi.fn().mockImplementation(() => discordChannels);
        DiscordChannelApi.occupied = vi.fn().mockImplementation(() => []);
        TagsApi.index = vi.fn().mockImplementation(() => [
            { id: 1, name: 'Laravel' },
            { id: 2, name: 'Vue' },
        ]);
        AnydesksApi.getAllAnyDesks = vi.fn().mockImplementation(() => anyDesks);
        wrapper = mount(GrowthSessionForm, { propsData: { owner: user, startDate } });
    });

    const baseGrowthSessionRequest: IStoreGrowthSessionRequest = {
        location: 'The growth session location',
        topic: 'Chosen topic',
        title: 'Chosen title',
        date: '2020-10-01',
        start_time: '04:45 pm',
        discord_channel_id: undefined,
        anydesk_id: undefined,
        allow_watchers: true,
    };
    describe('used for creation', () => {
        it('focuses the title input when the form opens', async () => {
            wrapper.unmount();
            wrapper = mount(GrowthSessionForm, { propsData: { owner: user, startDate }, attachTo: document.body });
            await flushPromises();

            expect(document.activeElement).toBe(wrapper.find('#title').element);
            wrapper.unmount();
        });

        describe('allows a growth session to be created', () => {
            type TGrowthSessionCreationScenario = [string, IStoreGrowthSessionRequest];

            const scenarios: TGrowthSessionCreationScenario[] = [
                ['Can accept no limit', { ...baseGrowthSessionRequest, attendee_limit: undefined }],
                [
                    'Can accept a limit',
                    {
                        ...baseGrowthSessionRequest,
                        attendee_limit: 4,
                    },
                ],
                [
                    'Can accept an end time',
                    {
                        ...baseGrowthSessionRequest,
                        end_time: '5:45 pm',
                    },
                ],
                ['Can accept no Discord channel', { ...baseGrowthSessionRequest, discord_channel_id: undefined }],
                ['Can accept a Discord channel', { ...baseGrowthSessionRequest, discord_channel_id: '1234567890' }],
                ['Can set the growth session to allow watchers', { ...baseGrowthSessionRequest, allow_watchers: true }],
                ['Can set the growth session to not allow watchers', { ...baseGrowthSessionRequest, allow_watchers: false }],
                ['Can accept no AnyDesk', { ...baseGrowthSessionRequest, anydesk_id: undefined }],
                ['Can accept an AnyDesk', { ...baseGrowthSessionRequest, anydesk_id: 1 }],
                ['Can accept tags', { ...baseGrowthSessionRequest, tags: [1] }],
            ];

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
                    tags: chosenTags,
                } = payload;

                const discordChannel = discordChannels.filter((channel) => channel.id === discordChannelId)[0] || undefined;
                const anyDesk = anyDesks.filter((desk) => desk.id === anyDeskId)[0] || undefined;

                await flushPromises();

                window.confirm = vi.fn();

                wrapper.find('#start-time').setValue(DateTime.parseByTime(chosenStartTime).toTimeString24Hours());
                wrapper.find('#title').setValue(chosenTitle);
                wrapper.find('#topic').setValue(chosenTopic);
                wrapper.find('#location').setValue(chosenLocation);

                if (!chosenLimit) {
                    wrapper.find('#no-limit').trigger('click');
                }

                wrapper.find('#allow-watchers').setValue(allow_watchers);

                if (chosenLimit) {
                    wrapper.find('#attendee-limit').setValue(chosenLimit);
                }

                if (discordChannel) {
                    wrapper.find('#discord-channel').setValue(discordChannel.id);
                }

                if (anyDesk) {
                    wrapper.find('#anydesk-selection').setValue(anyDeskId!.toString());
                }

                if (chosenTags) {
                    for (const tagId of chosenTags) {
                        await wrapper.find(`[data-testid="tag-option-${tagId}"]`).trigger('click');
                    }
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

                if (chosenTags) {
                    expectedPayload.tags = chosenTags;
                }

                expect(GrowthSessionApi.store).toHaveBeenCalledWith(expect.objectContaining(expectedPayload));
            });
        });

        it('emits submitted event on success', async () => {
            wrapper.find('#start-time').setValue('09:30');
            wrapper.find('#title').setValue('Something');
            wrapper.find('#topic').setValue('Anything');
            wrapper.find('#location').setValue('Anywhere');
            await wrapper.vm.$nextTick();
            wrapper.find('button[type="submit"]').trigger('click');
            await flushPromises();
            expect(wrapper.emitted('submitted')).toBeTruthy();
        });

        it('starts with the submit button disabled', () => {
            expect(wrapper.find<HTMLButtonElement>('button[type="submit"]').element.disabled).toBeTruthy();
        });

        it('enables the submit button when all required fields are filled', async () => {
            wrapper.find('#start-time').setValue('09:30');
            wrapper.find('#title').setValue('Something');
            wrapper.find('#topic').setValue('Anything');
            wrapper.find('#location').setValue('Anywhere');
            await wrapper.vm.$nextTick();
            expect(wrapper.find<HTMLButtonElement>('button[type="submit"]').element.disabled).toBeFalsy();
        });

        it('has a no limit checkbox', () => {
            expect(wrapper.find('#no-limit').exists()).toBeTruthy();
        });

        it('starts with allow watchers checkbox enabled', () => {
            expect(wrapper.find<HTMLInputElement>('#allow-watchers').element.checked).toBe(true);
        });

        it('hides the attendee limit input, if no limit checkbox is checked', async () => {
            wrapper.find('#no-limit').setValue(true);
            await wrapper.vm.$nextTick();

            expect(wrapper.findComponent({ ref: 'attendee-limit' }).exists()).toBeFalsy();
        });

        it('displays a dropdown select with Discord channel names', async () => {
            await flushPromises();
            expect(DiscordChannelApi.index).toHaveBeenCalled();

            const discordChannelDropdown = wrapper.find('#discord-channel');
            expect(discordChannelDropdown.exists()).toBeTruthy();
            const optionsAvailable = discordChannelDropdown.findAll('option').map((option) => option.text());
            const optionsExpected = discordChannels.map((option) => option.name);
            expect(optionsAvailable).toEqual(expect.arrayContaining(optionsExpected));
        });

        it('does not display the dropdown select with Discord channel names if no channels are returned', async () => {
            DiscordChannelApi.index = vi.fn().mockImplementation(() => []);
            wrapper = mount(GrowthSessionForm, { propsData: { owner: user, startDate } });

            await flushPromises();

            expect(wrapper.find('#discord-channel').exists()).toBeFalsy();
        });

        it('displays a dropdown select with AnyDesks when AnyDesks given', async () => {
            await flushPromises();

            const anyDesksSelector = wrapper.find('#anydesk-selection');
            expect(anyDesksSelector.exists()).toBeTruthy();
            expect((anyDesksSelector.element as HTMLSelectElement).value).toBe('');
            expect(anyDesksSelector.find('option[value=""]').text()).toBe('None');
            const optionsAvailable = anyDesksSelector.findAll('option').map((option) => option.text());
            const optionsExpected = anyDesks.map((option) => option.name);
            expect(optionsAvailable).toEqual(expect.arrayContaining(optionsExpected));
        });

        it('does not display the dropdown select with AnyDesks when no AnyDesks given', async () => {
            AnydesksApi.getAllAnyDesks = vi.fn().mockImplementation(() => []);
            wrapper = mount(GrowthSessionForm, { propsData: { owner: user, startDate } });

            await flushPromises();

            const anyDesksSelector = wrapper.find('#anydesk-selection');
            expect(anyDesksSelector.exists()).toBe(false);
        });

        it('autofills the location with the Discord channel when selected', async () => {
            await flushPromises();
            const selector = wrapper.find('#discord-channel');
            const locationInput = wrapper.find('#location').element as HTMLInputElement;

            expect(locationInput.value).toBe('');

            await selector.setValue(discordChannels[0].id);
            expect(locationInput.value).toBe(`Channel: ${discordChannels[0].name}`);
        });

        it('changes the location when new Discord channel is selected if old location was a Discord channel', async () => {
            await flushPromises();
            const selector = wrapper.find('#discord-channel');
            const locationInput = wrapper.find('#location').element as HTMLInputElement;

            await selector.setValue(discordChannels[0].id);
            expect(locationInput.value).toBe(`Channel: ${discordChannels[0].name}`);

            await selector.setValue(discordChannels[1].id);
            expect(locationInput.value).toBe(`Channel: ${discordChannels[1].name}`);
        });

        it('allows users to create a public growth session', async () => {
            const growthSessionInformation: IStoreGrowthSessionRequest = {
                location: 'The growth session location',
                topic: 'Chosen topic',
                title: 'Chosen title',
                date: startDate,
                start_time: '4:45 pm',
                is_public: true,
            };

            await wrapper.find('#title').setValue(growthSessionInformation.title);
            await wrapper.find('#topic').setValue(growthSessionInformation.topic);
            await wrapper.find('#location').setValue(growthSessionInformation.location);
            await wrapper.find('#start-time').setValue(DateTime.parseByTime(growthSessionInformation.start_time).toTimeString12Hours());
            await wrapper.find('#is-public').setValue(true);

            wrapper.find('button[type=submit]').trigger('click');
            await wrapper.vm.$nextTick();

            await wrapper.find('.confirm-button').trigger('click');
            await flushPromises();

            expect(GrowthSessionApi.store).toHaveBeenCalledWith(expect.objectContaining({ is_public: true }));
        });

        it('shows confirmation modal when creating a public growth session', async () => {
            await wrapper.find('#title').setValue('Test Title');
            await wrapper.find('#topic').setValue('Test Topic');
            await wrapper.find('#location').setValue('Test Location');
            await wrapper.find('#is-public').setValue(true);

            await wrapper.find('button[type=submit]').trigger('click');
            await wrapper.vm.$nextTick();

            expect(wrapper.find('dialog').exists()).toBe(true);
            expect(wrapper.text()).toContain('Make Session Public?');
        });

        it('creates growth session after user confirms in modal', async () => {
            await wrapper.find('#title').setValue('Test Title');
            await wrapper.find('#topic').setValue('Test Topic');
            await wrapper.find('#location').setValue('Test Location');
            await wrapper.find('#is-public').setValue(true);

            await wrapper.find('button[type=submit]').trigger('click');
            await wrapper.vm.$nextTick();

            await wrapper.find('.confirm-button').trigger('click');
            await flushPromises();

            expect(GrowthSessionApi.store).toHaveBeenCalled();
        });

        it('does not create growth session when user cancels in modal', async () => {
            await wrapper.find('#title').setValue('Test Title');
            await wrapper.find('#topic').setValue('Test Topic');
            await wrapper.find('#location').setValue('Test Location');
            await wrapper.find('#is-public').setValue(true);

            await wrapper.find('button[type=submit]').trigger('click');
            await wrapper.vm.$nextTick();

            const cancelButton = wrapper.findAll('button').find((btn) => btn.text() === 'Cancel');
            await cancelButton?.trigger('click');
            await flushPromises();

            expect(GrowthSessionApi.store).not.toHaveBeenCalled();
        });

        it('does not show confirmation modal when creating a private growth session', async () => {
            await wrapper.find('#title').setValue('Test Title');
            await wrapper.find('#topic').setValue('Test Topic');
            await wrapper.find('#location').setValue('Test Location');

            await wrapper.find('button[type=submit]').trigger('click');
            await wrapper.vm.$nextTick();

            expect(wrapper.find('dialog').exists()).toBe(false);
            expect(GrowthSessionApi.store).toHaveBeenCalled();
        });

        it('offers the invite link toggle, off by default, with no link to show yet', () => {
            expect(wrapper.find<HTMLInputElement>('#has-invite-link').element.checked).toBe(false);
            expect(wrapper.text()).toContain('Shareable invite link');
            expect(wrapper.find('.share-url').exists()).toBe(false);
        });

        it('requests an invite link when the toggle is on', async () => {
            await wrapper.find('#title').setValue('Test Title');
            await wrapper.find('#topic').setValue('Test Topic');
            await wrapper.find('#location').setValue('Test Location');
            await wrapper.find('#has-invite-link').setValue(true);

            await wrapper.find('button[type=submit]').trigger('click');
            await flushPromises();

            expect(GrowthSessionApi.store).toHaveBeenCalledWith(expect.objectContaining({ has_invite_link: true }));
        });

        it('does not show a confirmation modal when the invite link toggle is on', async () => {
            await wrapper.find('#title').setValue('Test Title');
            await wrapper.find('#topic').setValue('Test Topic');
            await wrapper.find('#location').setValue('Test Location');
            await wrapper.find('#has-invite-link').setValue(true);

            await wrapper.find('button[type=submit]').trigger('click');
            await wrapper.vm.$nextTick();

            expect(wrapper.find('dialog').exists()).toBe(false);
            expect(GrowthSessionApi.store).toHaveBeenCalled();
        });

        it('disables the invite link toggle while public is on, and explains why', async () => {
            expect(wrapper.find<HTMLInputElement>('#has-invite-link').element.disabled).toBe(false);

            await wrapper.find('#is-public').setValue(true);

            expect(wrapper.find<HTMLInputElement>('#has-invite-link').element.disabled).toBe(true);
            expect(wrapper.text()).toContain('Public growth sessions are already visible to everyone');
        });

        it('turns the invite link off when the session is made public', async () => {
            await wrapper.find('#has-invite-link').setValue(true);
            await wrapper.find('#is-public').setValue(true);

            expect(wrapper.find<HTMLInputElement>('#has-invite-link').element.checked).toBe(false);

            await wrapper.find('#title').setValue('Test Title');
            await wrapper.find('#topic').setValue('Test Topic');
            await wrapper.find('#location').setValue('Test Location');
            await wrapper.find('button[type=submit]').trigger('click');
            await wrapper.vm.$nextTick();
            await wrapper.find('.confirm-button').trigger('click');
            await flushPromises();

            expect(GrowthSessionApi.store).toHaveBeenCalledWith(expect.objectContaining({ is_public: true, has_invite_link: false }));
        });

        it('brings the invite link back when public is turned off again', async () => {
            await wrapper.find('#has-invite-link').setValue(true);
            await wrapper.find('#is-public').setValue(true);
            await wrapper.find('#is-public').setValue(false);

            expect(wrapper.find<HTMLInputElement>('#has-invite-link').element.checked).toBe(true);
        });
    });

    describe('used for editing', () => {
        beforeEach(() => {
            wrapper = mount(GrowthSessionForm, {
                propsData: {
                    owner: user,
                    startDate,
                    growthSession: growthSessionWithCommentsJson,
                },
            });
        });

        it('allows is public field to be editable', () => {
            expect(wrapper.find('#is-public').exists()).toBe(true);
        });

        it('shows confirmation modal when updating a private session to public', async () => {
            const privateGrowthSession = { ...growthSessionWithCommentsJson, is_public: false };
            wrapper = mount(GrowthSessionForm, {
                propsData: {
                    owner: user,
                    startDate,
                    growthSession: privateGrowthSession,
                },
            });

            await wrapper.find('#is-public').setValue(true);
            await wrapper.find('button[type=submit]').trigger('click');
            await wrapper.vm.$nextTick();

            expect(wrapper.find('dialog').exists()).toBe(true);
            expect(wrapper.text()).toContain('Make Session Public?');
        });

        it('does not show confirmation modal when updating an already public session', async () => {
            const publicGrowthSession = { ...growthSessionWithCommentsJson, is_public: true };
            wrapper = mount(GrowthSessionForm, {
                propsData: {
                    owner: user,
                    startDate,
                    growthSession: publicGrowthSession,
                },
            });

            await wrapper.find('button[type=submit]').trigger('click');
            await wrapper.vm.$nextTick();

            expect(wrapper.find('dialog').exists()).toBe(false);
            expect(GrowthSessionApi.update).toHaveBeenCalled();
        });

        it('shows the invite link toggle on when a link is currently live', () => {
            wrapper = mount(GrowthSessionForm, {
                propsData: {
                    owner: user,
                    startDate,
                    growthSession: { ...growthSessionWithCommentsJson, share_url: 'https://growth.test/invitations/abc123' },
                },
            });

            expect(wrapper.find<HTMLInputElement>('#has-invite-link').element.checked).toBe(true);
        });

        it('shows the invite link toggle off when no link is live', () => {
            wrapper = mount(GrowthSessionForm, {
                propsData: {
                    owner: user,
                    startDate,
                    growthSession: { ...growthSessionWithCommentsJson, share_url: undefined },
                },
            });

            expect(wrapper.find<HTMLInputElement>('#has-invite-link').element.checked).toBe(false);
        });

        it('revokes the invite link when an unlisted session is made public', async () => {
            wrapper = mount(GrowthSessionForm, {
                propsData: {
                    owner: user,
                    startDate,
                    growthSession: { ...growthSessionWithCommentsJson, is_public: false, share_url: 'https://growth.test/invitations/abc123' },
                },
            });

            await wrapper.find('#is-public').setValue(true);
            await wrapper.find('button[type=submit]').trigger('click');
            await wrapper.vm.$nextTick();
            await wrapper.find('.confirm-button').trigger('click');
            await flushPromises();

            expect(GrowthSessionApi.update).toHaveBeenCalledWith(
                expect.anything(),
                expect.objectContaining({ is_public: true, has_invite_link: false }),
            );
        });
    });
});
