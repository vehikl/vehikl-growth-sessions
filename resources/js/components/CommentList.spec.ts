import {mount, Wrapper} from '@vue/test-utils';
import CommentList from './CommentList.vue';
import {SocialMob} from '../classes/SocialMob';
import socialMobWithCommentsJson from '../../../tests/fixtures/socialMobWithComments.json';
import {SocialMobApi} from '../services/SocialMobApi';

const socialMob: SocialMob = new SocialMob(socialMobWithCommentsJson);

describe('CommentList', () => {
    let wrapper: Wrapper<CommentList>;

    beforeEach(() => {
        wrapper = mount(CommentList, {propsData: {socialMob}})
    });
    it('displays all comments of a given mob', () => {
        socialMob.comments.map(comment => comment.content).forEach(comment => {
            expect(wrapper.text()).toContain(comment);
        });
    });

    it('allows a new comment to be created', async () => {
        SocialMobApi.comment = jest.fn().mockResolvedValue(socialMob);
        const comment = 'My comment';

        wrapper.find('#new-comment').setValue(comment);
        wrapper.find('form').trigger('submit');

        expect(SocialMobApi.comment).toHaveBeenCalled();
    });
});
