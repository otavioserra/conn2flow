import React from 'react'
import {
    Text,
    StyleSheet,
    View,
    Platform,
} from 'react-native'

import {connect} from 'react-redux';
import { signOut } from '../redux/actions';

import Avatar from '../components/Avatar'
import Header from '../components/Header'
import Footer from '../components/Footer'

class PerfilScreen extends React.Component {
    render() {
        return (
            <View style={styles.conteiner}>
                <Header icon='user' title='Perfil' navigation={this.props.navigation}></Header>
                <View style={styles.area}>
                    <Avatar width={100}></Avatar>
                    <Text style={styles.nome}>{this.props.userData.nome}</Text>
                    <Text style={styles.codigo}>Código: {this.props.userData.codigo}</Text>
                </View>
                <Footer navigation={this.props.navigation}></Footer>
            </View>
        )
    }
}

const mapStateToProps = state => {
    return {
        userData: state.loginReducer.userData
    }
}

export default connect(mapStateToProps,{signOut})(PerfilScreen)

const styles = StyleSheet.create({
    conteiner:{
        paddingTop: Platform.OS === 'ios' ? 20 : 0,
        flex:1,
        justifyContent:'space-between',
        alignItems: 'center',
        backgroundColor:'#212121'
    },
    area:{
        flex:5,
        width:'100%',
        paddingTop:65,
        justifyContent:'flex-start',
        alignItems: 'center',
        paddingLeft:24,
        paddingRight:24,
    },
    nome:{
        fontFamily: "OpenSans-Bold",
        fontWeight:'bold',
        color:'#FFF',
        fontSize:24,
        textAlign:'center',
        marginTop:15,
    },
    codigo:{
        fontFamily: "OpenSans-Regular",
        color:'#FFF',
        fontSize:14,
        textAlign:'center',
        marginTop:5
    },
})