import Vue from 'vue';
import Vuex from 'vuex';
import persistedState from 'vuex-persistedstate';

Vue.use(Vuex);

export default new Vuex.Store({
    'state': {
        'bduss': '',
        'active': '2'
    },
    'mutations': {
        setBduss(state, data) {
            state.bduss = data;
        }
    },
    'plugins': [
        persistedState({
            'storage': window.localStorage,
            reducer(val) {
                return {
                    'bduss': val.bduss
                };
            }
        })
    ]
});
