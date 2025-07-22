import React from 'react';
import {
    Text,
    View,
    FlatList,
    StyleSheet,
    TouchableOpacity,
    Image,
    Platform,
} from 'react-native'

import Icon from 'react-native-vector-icons/FontAwesome'

import * as SecureStore from 'expo-secure-store';

import {connect} from 'react-redux';
import { signOut, showCamera } from '../redux/actions';

import logo from '../../assets/imgs/entrey-logomarca.png'

class HomeScreen extends React.Component {
    constructor(props) {
        super(props);

        this.goToScreen = this.goToScreen.bind(this);
    }

    goToScreen(nav){
        switch(nav){
            case 'BaixarVoucher':
                this.props.showCamera();
            break;
        }

        this.props.navigation.navigate(nav);
    }

    logout = async () => {
        await SecureStore.deleteItemAsync('userToken')
        await SecureStore.deleteItemAsync('tokenExpire')
        await SecureStore.deleteItemAsync('userData')

        this.props.signOut();
    }

    render() {
        return (
            <View style={styles.conteiner}>
                <View style={styles.logoCont}>
                    <Image style={styles.image} source={logo} />
                </View>
                <View style={styles.list}>
                    {global.screens.map(item => 
                        <View style={styles.listItem}>
                            <TouchableOpacity style={styles.button} onPress={() => this.goToScreen(item.id)}>
                                <Icon name={item.icon} size={25} color='#FFF' style={styles.icon} />
                                <Text style={styles.buttonText}>{item.nome}</Text>
                            </TouchableOpacity>
                        </View>
                    )}
                </View>
                <View style={styles.bottomCont}>
                    <TouchableOpacity style={styles.buttonSair} onPress={this.logout}>
                        <Icon name='close' size={25} color='#FFF' style={styles.icon} />
                        <Text style={styles.buttonText}>Sair</Text>
                    </TouchableOpacity>
                </View>
            </View>
        )
    }
}

export default connect(null,{signOut,showCamera})(HomeScreen)

const styles = StyleSheet.create({
    conteiner:{
        paddingTop: Platform.OS === 'ios' ? 20 : 0,
        flex:1,
        flexDirection: "column",
        justifyContent:'flex-start',
        alignItems: 'center',
        paddingLeft:24,
        paddingRight:24,
        backgroundColor:'#212121'
    },
    logoCont: {
        flex:1,
        justifyContent:"center",
        alignItems: 'center',
    },
    image: {
        width: 200,
        height:70,
        resizeMode: 'contain'
    },
    button:{
        width:140,
        height:70,
        backgroundColor:'#4b84ea',
        justifyContent:"center",
        alignItems: 'center',
        borderRadius:15,
        margin:20,
    },
    buttonSair:{
        width:140,
        height:70,
        backgroundColor:'#ed6f66',
        justifyContent:"center",
        alignItems: 'center',
        borderRadius:15,
        margin:20,
    },
    list:{
        flex:1,
        flexDirection: "row",
    },
    listItem:{
        
    },
    bottomCont:{
        flex:1,
        justifyContent:"flex-end",
    },
    buttonText:{
        fontFamily: "OpenSans-Bold",
        fontWeight: 'bold',
        color:'#FFF',
        fontSize:16,
    },
})