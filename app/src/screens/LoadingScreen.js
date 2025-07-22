import React from 'react';
import Loading from '../components/Loading'

export default class LoadingScreen extends React.Component {
    render() {
        return (
            <Loading loading={true} logo={true}></Loading>
        )
    }
}