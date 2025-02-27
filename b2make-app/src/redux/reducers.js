import {APP_STARTED, RESTORE_TOKEN, RENEW_TOKEN, SIGN_IN, SIGN_OUT, SHOW_CAMERA, HIDE_CAMERA} from './actions';

const loginInitialState = {
    isLoading: true,
    isSignout: false,
    userToken: null,
    cameraShow: false,
    tokenExpire: 0,
    userData: {},
};

export function loginReducer(state = loginInitialState, action) {
    switch (action.type) {
        case APP_STARTED:
            return {
                ...state,
                isLoading: false,
            };
        case RESTORE_TOKEN:
            return {
                ...state,
                userToken: action.payload.userToken,
                tokenExpire: action.payload.tokenExpire,
                userData: action.payload.userData,
                isLoading: false,
            };
        case RENEW_TOKEN:
            return {
                ...state,
                userToken: action.payload.userToken,
                tokenExpire: action.payload.tokenExpire,
            };
        case SIGN_IN:
            return {
                ...state,
                isSignout: false,
                userToken: action.payload.userToken,
                tokenExpire: action.payload.tokenExpire,
                userData: action.payload.userData,
            };
        case SIGN_OUT:
            return {
                ...state,
                isSignout: true,
                userToken: null,
                tokenExpire: 0,
                userData: {},
            };
        case SHOW_CAMERA:
            return {
                ...state,
                cameraShow: true,
            };
        case HIDE_CAMERA:
            return {
                ...state,
                cameraShow: false,
            };
        default:
            return state;
    }
}