import React from 'react';
import {
    StyleSheet,
    View,
    Text,
    TouchableOpacity,
} from 'react-native';

import LinearGradient from 'react-native-linear-gradient';

export default class Footer extends React.Component {
    back = () => {
        let navID = (this.props.navID ? this.props.navID : "Home")

        if(this.props.hideCamera){
            this.props.hideCamera();
        }

        this.props.navigation.navigate(navID);
    }

    render() {
        return (
            <View style={styles.base}>
                <TouchableOpacity onPress={this.back} style={styles.button}>
                    <LinearGradient useAngle={true} angle={220} angleCenter={{ x: 0.5, y: 0.5}} colors={['#ff2b67', '#ff5e3a']} style={styles.linearGradient}>
                        <Text style={styles.buttonText}>Voltar</Text>
                    </LinearGradient>
                </TouchableOpacity>
            </View>
        )
    }
}

const styles = StyleSheet.create({
    base:{
        flex:1,
        width:'100%',
        justifyContent:'center',
        alignItems: 'center',
        paddingLeft:24,
        paddingRight:24,
        backgroundColor:'#333',
    },
    button:{
        width:'100%',
        height:50,
        borderRadius:25,
        justifyContent:"center",
        alignItems: 'center',
    },
    linearGradient:{
        width:'100%',
        height:50,
        borderRadius:25,
        flex:1,
        justifyContent:"center",
        alignItems: 'center',
    },
    buttonText:{
        fontFamily: "OpenSans-Regular",
        color:'#FFF',
        fontSize:16,
    },
})