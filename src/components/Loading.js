import React from 'react';
import {
    StyleSheet,
    View,
    ActivityIndicator,
    Modal,
    Image
} from 'react-native';

import logo from '../../assets/imgs/logomarca-simbolo.png'

export default class Loading extends React.Component {
    render() {
        let logoCont = null

        if(this.props.logo){
            logoCont = (
                <Image style={styles.image} source={logo} />
            )
        }

        return (
            <View style={styles.mainContainer}>
                <Modal
                    visible={this.props.loading}
                    transparent={true}
                    animationType={"fade"} >
                        <View style={styles.background}>
                            {logoCont}
                            <ActivityIndicator style={styles.indicator} size="large" color="#fff" />
                        </View>
                </Modal>
            </View>
        )
    }
}

const styles = StyleSheet.create({
    mainContainer :{
        position:'absolute',
        width:'100%',
        height:'100%',
        justifyContent: 'center',
        alignItems: 'center',
        marginTop: (Platform.OS == 'ios') ? 20 : 0,
    },
    background:{
        flex:1,
        alignItems:'center',
        justifyContent: 'center',
        backgroundColor:'rgba(48, 48, 48, 0.9)'
    },
    image: {
        width: 180,
        height:70,
        resizeMode: 'contain',
        marginBottom:20
    },
    indicator:{
    }
})