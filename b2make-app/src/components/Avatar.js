import React from 'react'
import {
    StyleSheet,
    View,
    Image,
} from 'react-native'

import userProfile from '../../assets/imgs/user-profile.png'

export default class Avatar extends React.Component {
    render() {
        let avatar = null
        if(this.props.avatar){
            avatar = (
                <Image style={styles.image} source={{uri:'https://'+(global.beta ? 'beta.':'')+'entrey.com.br/'+this.props.avatar}} />
            )
        } else {
            avatar = (
                <Image style={styles.image} source={userProfile} />
            )
        }
        
        return (
            <View style={{width:this.props.width ? this.props.width : 60,height:this.props.width ? this.props.width : 60,borderRadius:this.props.width ? this.props.width : 60,overflow:'hidden'}}>{avatar}</View>
        )
    }
}

const styles = StyleSheet.create({
    text:{
        fontFamily: "OpenSans-Bold",
        color:'#FFF',
        fontSize:16
    },
    image:{
        width:'100%',
        height:'100%',
        resizeMode:'contain',
    }
})