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

    it('renders an image URL in a comment as an embedded image', () => {
        const imageUrl = 'https://example.com/funny.gif';
        const sessionWithImageComment = new GrowthSession({
            ...growthSessionWithCommentsJson,
            comments: [{ ...growthSessionWithCommentsJson.comments[0], content: `look at this ${imageUrl}` }],
        });
        wrapper = mount(CommentList, { propsData: { growthSession: sessionWithImageComment, user } });

        expect(wrapper.find('p img').attributes('src')).toBe(imageUrl);
        expect(wrapper.find('p').text()).toContain('look at this');
    });

    it('renders comment content without an image URL as plain text', () => {
        expect(wrapper.find('p img').exists()).toBe(false);
    });

    it('renders an image URL as plain text when the comment author is not a Vehikalien', () => {
        const imageUrl = 'https://example.com/funny.gif';
        const sessionWithImageComment = new GrowthSession({
            ...growthSessionWithCommentsJson,
            comments: [
                {
                    ...growthSessionWithCommentsJson.comments[0],
                    content: `look at this ${imageUrl}`,
                    user: { ...growthSessionWithCommentsJson.comments[0].user, is_vehikl_member: false },
                },
            ],
        });
        wrapper = mount(CommentList, { propsData: { growthSession: sessionWithImageComment, user } });

        expect(wrapper.find('p img').exists()).toBe(false);
        expect(wrapper.find('p').text()).toContain(imageUrl);
    });

    it('falls back to the raw URL when an embedded image fails to load', async () => {
        const imageUrl = 'https://example.com/dead-link.gif';
        const sessionWithImageComment = new GrowthSession({
            ...growthSessionWithCommentsJson,
            comments: [{ ...growthSessionWithCommentsJson.comments[0], content: imageUrl }],
        });
        wrapper = mount(CommentList, { propsData: { growthSession: sessionWithImageComment, user } });

        await wrapper.find('p img').trigger('error');

        expect(wrapper.find('p img').exists()).toBe(false);
        expect(wrapper.find('p').text()).toBe(imageUrl);
    });
});
