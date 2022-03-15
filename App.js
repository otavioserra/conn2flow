import * as React from 'react';
import {Provider} from 'react-redux';

import BootStrap from './src/bootstrap';
import {store} from './src/redux/store';

export default function App({ navigation }) {
    return (
        <Provider store={store}>
            <BootStrap />
        </Provider>
    );
}