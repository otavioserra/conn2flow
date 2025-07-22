// Definições de ações.

export const APP_STARTED = 'APP_STARTED';
export const RESTORE_TOKEN = 'RESTORE_TOKEN';
export const RENEW_TOKEN = 'RENEW_TOKEN';
export const SIGN_IN = 'SIGN_IN';
export const SIGN_OUT = 'SIGN_OUT';
export const SHOW_CAMERA = 'SHOW_CAMERA';
export const HIDE_CAMERA = 'HIDE_CAMERA';

// Definições de funções de ações.

export const appStarted = () => dispatch => {
    dispatch({
        type: APP_STARTED,
    });
};

export const restoreToken = data => dispatch => {
    dispatch({
        type: RESTORE_TOKEN,
        payload: data,
    });
};

export const renewToken = data => dispatch => {
    dispatch({
        type: RENEW_TOKEN,
        payload: data,
    });
};

export const signIn = data => dispatch => {
    dispatch({
        type: SIGN_IN,
        payload: data,
    });
};

export const signOut = () => dispatch => {
    dispatch({
        type: SIGN_OUT,
    });
};

export const showCamera = () => dispatch => {
    dispatch({
        type: SHOW_CAMERA,
    });
};

export const hideCamera = () => dispatch => {
    dispatch({
        type: HIDE_CAMERA,
    });
};
