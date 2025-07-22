import {createStore, combineReducers, applyMiddleware} from 'redux';
import thunk from 'redux-thunk';

import {loginReducer} from './reducers';

const rootReducer = combineReducers({loginReducer});

export const store = createStore(rootReducer, applyMiddleware(thunk));