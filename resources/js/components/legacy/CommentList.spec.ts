import {mount} from "@vue/test-utils"
import CommentList from "./CommentList.vue"
import {GrowthSession} from "@/classes/GrowthSession"
import growthSessionWithCommentsJson from "@/../../tests/fixtures/GrowthSessionWithComments.json"
import userJson from "@/../../tests/fixtures/User.json"
import {GrowthSessionApi} from "@/services/GrowthSessionApi"
import {IUser} from "@/types"
import {User} from "@/classes/User"
import {vi} from "vitest"
import {VueWrapper} from "@vue/test-utils/dist/vueWrapper"

const growthSession: GrowthSession = new GrowthSession(growthSessionWithCommentsJson)
const user: IUser = userJson

describe("CommentList", () => {
    let wrapper: VueWrapper

    beforeEach(() => {
        wrapper = mount(CommentList, {propsData: {growthSession, user}})
    })

    it('displays all comments of a given growth session', () => {
        growthSession.comments.map(comment => comment.content).forEach(comment => {
            expect(wrapper.text()).toContain(comment);
        });
    });

    it('allows a new comment to be created', async () => {
        GrowthSessionApi.postComment = vi.fn().mockResolvedValue(growthSession)
        const comment = 'My comment';

        wrapper.find('#new-comment').setValue(comment);
        wrapper.find('form').trigger('submit');

        expect(GrowthSessionApi.postComment).toHaveBeenCalled();
    });

    it('disables the new comment form for guests',() => {
        wrapper = mount(CommentList, {propsData: {growthSession, user: null}})

        expect(wrapper.find("#new-comment").element.disabled).toBeTruthy()
        expect(wrapper.find("#submit-new-comment").element.disabled).toBeTruthy()
    });

    it('redirects to the commenters GitHub page when clicked on the avatar', () => {
        const commenterComponent = wrapper.findAll("[aria-label=visit-their-github]")

        commenterComponent.forEach((attendeeComponent, i) => {
            const commenter = new User(growthSession.comments[i].user)
            expect(attendeeComponent.attributes("href")).toEqual(commenter.githubURL)
        })
    })
});
