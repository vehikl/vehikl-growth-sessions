import {shallowMount, Wrapper} from '@vue/test-utils';
import ExampleComponent from './ExampleComponent.vue';

describe('ExampleComponent', () => {
    it('renders the example text', () => {
       const wrapper: Wrapper<ExampleComponent> = shallowMount(ExampleComponent);
       expect(wrapper.text()).toContain('Example Component')
    });
});
