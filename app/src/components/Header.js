import React from 'react';
import {
    StyleSheet,
    View,
    Text,
    TouchableOpacity,
} from 'react-native';

import Icon from 'react-native-vector-icons/FontAwesome'

export default class Header extends React.Component {
    openMenu = () => {
        this.props.navigation.openDrawer();
    }

    render() {
        return (
            <View style={styles.conteiner}>
                <View style={styles.menu}>
                    <TouchableOpacity onPress={this.openMenu} style={styles.button}>
                        <Icon name='navicon' size={35} color='#000' style={styles.iconMenu} />
                    </TouchableOpacity>
                </View>
                <View style={styles.title}>
                    <Icon name={this.props.icon ? this.props.icon : 'minus-square-o'} size={35} color='#4b84ea' style={styles.icon} />
                    <Text style={styles.titleText}>{this.props.title ? this.props.title : 'Indefinido'}</Text>
                </View>
            </View>
        )
    }
}

const styles = StyleSheet.create({
    conteiner:{
        height:90,
        flexDirection:'row',
        justifyContent:"space-between",
        alignItems: 'center',
        backgroundColor:'#f3f3f3',
        paddingLeft:20,
        paddingRight:20,
        shadowColor: "#140000",
        shadowOffset: {
            width: 0,
            height: 3,
        },
        shadowOpacity: 0.27,
        shadowRadius: 4.65,
        elevation: 6,
    },
    menu:{
        flex:1,
    },
    title:{
        flex:1,
        flexDirection:'row',
        justifyContent:"flex-end",
        alignItems: 'center',
    },
    icon:{
        marginRight:10,
        marginTop:0,
    },
    iconMenu:{

    },
    titleText:{
        fontFamily: "OpenSans-Bold",
        fontWeight:'bold',
        fontSize:20,
        color:'#4b84ea'
    },
    button:{
        
    },
})