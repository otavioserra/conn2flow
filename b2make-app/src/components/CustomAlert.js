import React from 'react';
import { 
    Platform,
    StyleSheet,
    View,
    Text,
    Modal,
    TouchableOpacity,
    ScrollView,
    Image
} from 'react-native';

import icon_neg from '../../assets/imgs/icon-negativo.png'
import icon_pos from '../../assets/imgs/icon-positivo.png'

export default class CustomAlert extends React.Component {
    _isMounted = false

    constructor(props) {
        super(props)

        this.state = {
            visible: false,
        }
    }

    showHideAlert = (visible) => {
        this.setState({visible})
        this.props.options.open = visible

        if(this.props.okPressed){
            this.props.okPressed()
        }
    }

    componentDidMount = () => {
        this._isMounted = true;
    }

    componentDidUpdate = () => {
        if(this._isMounted)
        if(this.props.options.open != this.state.visible){
            this.setState({visible:this.props.options.open})
        }
    }

    render() {
        let icon = null;
        let buttonStyle = null;

        if(this.props.options.type == 'erro'){
            buttonStyle = styles.alertButtonErro
            icon = (
                <Image style={styles.alertImage} source={icon_neg} />
            )
        } else {
            buttonStyle = styles.alertButtonOk
            icon = (
                <Image style={styles.alertImage} source={icon_pos} />
            )
        }

        return (
            <View style={styles.mainContainer}>
                <Modal
                    visible={this.state.visible}
                    transparent={true}
                    animationType={"fade"}
                    onRequestClose={ () => { this.showHideAlert(!this.state.visible)} } >
                        <View style={styles.alertBackground}>
                            <View style={styles.alertMainView}>
                                {icon}
                                <ScrollView>
                                    <Text style={styles.alertTitle}>{this.props.options.title}</Text>
                                    <Text style={styles.alertMessage}>{this.props.options.msg}</Text>
                                </ScrollView>
                                <View style={[styles.alertButton,buttonStyle]}>
                                    <TouchableOpacity 
                                        style={styles.buttonStyle} 
                                        onPress={() => { this.showHideAlert(!this.state.visible)} } 
                                        activeOpacity={0.7} 
                                        >
                                            <Text style={styles.textStyle}>Ok</Text>
                                    </TouchableOpacity>
                                </View>
                            </View>
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
    alertMainView:{
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor : "#f3f3f3", 
        height: 300,
        width: '86%',
        borderRadius:15,
    },
    alertBackground:{
        flex:1,
        alignItems:'center',
        justifyContent: 'center',
        backgroundColor:'rgba(48, 48, 48, 0.9)'
    },
    alertImage:{
        width:60,
        height:60,
        marginTop:30,
        marginBottom:10
    },
    alertTitle:{
        fontSize: 20, 
        color: "#303030",
        fontFamily: "OpenSans-Bold",
        textAlign: 'center',
        padding: 10,
    },
    alertMessage:{
        fontSize: 16, 
        color: "#707070",
        fontFamily: "OpenSans-Regular",
        textAlign: 'center',
        padding: 10,
    },
    alertButton:{
        flexDirection: 'row',
        height:45,
        borderBottomLeftRadius:15,
        borderBottomRightRadius:15,
        marginTop:10
    },
    alertButtonErro:{
        backgroundColor: '#ea5454',
    },
    alertButtonOk:{
        backgroundColor: '#59c75e',
    },
    buttonStyle: {
        width: '100%',
        height: '100%',
        justifyContent: 'center',
        alignItems: 'center'
    },
    textStyle:{
        color:'#f3f3f3',
        textAlign:'center',
        fontSize: 16,
        fontFamily: "Open Sans Bold",
    }
})