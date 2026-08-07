import growthSessionWithCommentsJson from '@/../../tests/fixtures/GrowthSessionWithComments.json';
import userJson from '@/../../tests/fixtures/User.json';
import { GrowthSession } from '@/classes/GrowthSession';
import { User } from '@/classes/User';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { IUser } from '@/types';
import { mount, type VueWrapper } from '@vue/test-utils';
import { vi } from 'vitest';
import CommentList from './CommentList.vue';

const growthSession: GrowthSession = new GrowthSession(growthSessionWithCommentsJson);
const user: IUser = userJson;

describe('CommentList', () => {
    let wrapper: VueWrapper;

    beforeEach(() => {
        wrapper = mount(CommentList, { propsData: { growthSession, user } });
    });

    it('displays all comments of a given growth session', () => {
        growthSession.comments
            .map((comment) => comment.content)
            .forEach((comment) => {
                expect(wrapper.text()).toContain(comment);
            });
    });

    it('displays the comment count beside the header', () => {
        expect(wrapper.find('h2').text()).toBe(`Comments (${growthSession.comments.length})`);
    });

    it('allows a new comment to be created', async () => {
        GrowthSessionApi.postComment = vi.fn().mockResolvedValue(growthSession);
        const comment = 'My comment';

        wrapper.find('#new-comment').setValue(comment);
        wrapper.find('form').trigger('submit');

        expect(GrowthSessionApi.postComment).toHaveBeenCalled();
    });

    it('disables the new comment form for guests', () => {
        wrapper = mount(CommentList, { propsData: { growthSession, user: undefined } });

        expect(wrapper.find<HTMLTextAreaElement>('#new-comment').element.disabled).toBeTruthy();
        expect(wrapper.find<HTMLButtonElement>('#submit-new-comment').element.disabled).toBeTruthy();
    });

    it('redirects to the commenters GitHub page when clicked on the avatar', () => {
        const commenterComponent = wrapper.findAll('[aria-label=visit-their-github]');

        commenterComponent.forEach((attendeeComponent, i) => {
            const commenter = new User(growthSession.comments[i].user);
            expect(attendeeComponent.attributes('href')).toEqual(commenter.githubURL);
        });
    });

    it('renders an image segment as an embedded image', () => {
        const imageUrl = 'https://example.com/funny.gif';
        const sessionWithImageComment = new GrowthSession({
            ...growthSessionWithCommentsJson,
            comments: [
                {
                    ...growthSessionWithCommentsJson.comments[0],
                    segments: [
                        { type: 'text', value: 'look at this ' },
                        { type: 'image', value: imageUrl },
                    ],
                },
            ],
        });
        wrapper = mount(CommentList, { propsData: { growthSession: sessionWithImageComment, user } });

        expect(wrapper.find('p img').attributes('src')).toBe(imageUrl);
        expect(wrapper.find('p').text()).toContain('look at this');
    });

    it('renders comment content without an image segment as plain text', () => {
        expect(wrapper.find('p img').exists()).toBe(false);
    });

    it('renders a text segment as plain text, e.g. when the comment author is not a Vehikalien', () => {
        const imageUrl = 'https://example.com/funny.gif';
        const sessionWithImageComment = new GrowthSession({
            ...growthSessionWithCommentsJson,
            comments: [
                {
                    ...growthSessionWithCommentsJson.comments[0],
                    segments: [{ type: 'text', value: `look at this ${imageUrl}` }],
                },
            ],
        });
        wrapper = mount(CommentList, { propsData: { growthSession: sessionWithImageComment, user } });

        expect(wrapper.find('p img').exists()).toBe(false);
        expect(wrapper.find('p').text()).toContain(imageUrl);
    });

    it('renders each comment from its own segments, independent of other comments', () => {
        const memberImageUrl = 'https://example.com/member.gif';
        const guestImageUrl = 'https://example.com/guest.gif';
        const sessionWithMixedComments = new GrowthSession({
            ...growthSessionWithCommentsJson,
            comments: [
                {
                    ...growthSessionWithCommentsJson.comments[0],
                    id: 101,
                    segments: [{ type: 'image', value: memberImageUrl }],
                },
                {
                    ...growthSessionWithCommentsJson.comments[0],
                    id: 102,
                    segments: [{ type: 'text', value: guestImageUrl }],
                },
            ],
        });
        wrapper = mount(CommentList, { propsData: { growthSession: sessionWithMixedComments, user } });

        const commentParagraphs = wrapper.findAll('p');
        expect(commentParagraphs[0].find('img').attributes('src')).toBe(memberImageUrl);
        expect(commentParagraphs[1].find('img').exists()).toBe(false);
        expect(commentParagraphs[1].text()).toBe(guestImageUrl);
    });

    it('falls back to the raw URL when an embedded image fails to load', async () => {
        const imageUrl = 'https://example.com/dead-link.gif';
        const sessionWithImageComment = new GrowthSession({
            ...growthSessionWithCommentsJson,
            comments: [
                {
                    ...growthSessionWithCommentsJson.comments[0],
                    segments: [{ type: 'image', value: imageUrl }],
                },
            ],
        });
        wrapper = mount(CommentList, { propsData: { growthSession: sessionWithImageComment, user } });

        await wrapper.find('p img').trigger('error');

        expect(wrapper.find('p img').exists()).toBe(false);
        expect(wrapper.find('p').text()).toBe(imageUrl);
    });

    it('does not suppress the same image URL in a different comment when one fails to load', async () => {
        const imageUrl = 'https://example.com/shared.gif';
        const sessionWithSharedImageUrl = new GrowthSession({
            ...growthSessionWithCommentsJson,
            comments: [
                {
                    ...growthSessionWithCommentsJson.comments[0],
                    id: 201,
                    segments: [{ type: 'image', value: imageUrl }],
                },
                {
                    ...growthSessionWithCommentsJson.comments[0],
                    id: 202,
                    segments: [{ type: 'image', value: imageUrl }],
                },
            ],
        });
        wrapper = mount(CommentList, { propsData: { growthSession: sessionWithSharedImageUrl, user } });

        const commentParagraphs = wrapper.findAll('p');
        await commentParagraphs[0].find('img').trigger('error');

        expect(commentParagraphs[0].find('img').exists()).toBe(false);
        expect(commentParagraphs[1].find('img').attributes('src')).toBe(imageUrl);
    });

    it('does not suppress a repeated image URL within the same comment when one occurrence fails to load', async () => {
        const imageUrl = 'https://example.com/repeated.gif';
        const sessionWithRepeatedImageUrl = new GrowthSession({
            ...growthSessionWithCommentsJson,
            comments: [
                {
                    ...growthSessionWithCommentsJson.comments[0],
                    segments: [
                        { type: 'image', value: imageUrl },
                        { type: 'text', value: ' and again ' },
                        { type: 'image', value: imageUrl },
                    ],
                },
            ],
        });
        wrapper = mount(CommentList, { propsData: { growthSession: sessionWithRepeatedImageUrl, user } });

        const images = wrapper.findAll('p img');
        await images[0].trigger('error');

        const remainingImages = wrapper.findAll('p img');
        expect(remainingImages).toHaveLength(1);
        expect(remainingImages[0].attributes('src')).toBe(imageUrl);
    });
});
