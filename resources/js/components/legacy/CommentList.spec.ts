import rawGrowthSessionWithComments from '@/../../tests/fixtures/GrowthSessionWithComments.json';
import userJson from '@/../../tests/fixtures/User.json';
import { GrowthSession } from '@/classes/GrowthSession';
import { User } from '@/classes/User';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { IGrowthSession, IUser } from '@/types';
import { mount, type VueWrapper } from '@vue/test-utils';
import { vi } from 'vitest';
import CommentList from './CommentList.vue';

// Fixture JSON has no literal string types (e.g. `type: string`, not `type: 'text'`), unlike the
// discriminated unions the frontend types model - cast once here rather than at every spread below.
const growthSessionWithCommentsJson = rawGrowthSessionWithComments as unknown as IGrowthSession;
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

    it('renders an image segment as an embedded image via TextSegments', () => {
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
});
