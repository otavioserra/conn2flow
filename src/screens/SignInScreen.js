import React from 'react';
import {
    Text,
    StyleSheet,
    View,
    Image,
    ImageBackground,
    TextInput,
    TouchableOpacity,
    Platform,
    Keyboard,
    Linking,
} from 'react-native';

import {connect} from 'react-redux';
import { signIn } from '../redux/actions';

import * as SecureStore from 'expo-secure-store';

import LinearGradient from 'react-native-linear-gradient';

import Icon from 'react-native-vector-icons/FontAwesome'
import logo from '../../assets/imgs/entrey-logomarca.png'
import logo_simbolo from '../../assets/imgs/logomarca-simbolo.png'
import background from '../../assets/imgs/login-2.3.jpg'

import CustomAlert from '../components/CustomAlert'
import Loading from '../components/Loading'

class SignInScreen extends React.Component {
    state = {
        email: '',
        senha: '',
        editMode: false,
        inputOpen: false,
        loading: false,
        alert: {
            open: false,
            type: '',
            title: '',
            msg: '',
        },
    }

    componentDidMount() {
        this.keyboardDidHideListener = Keyboard.addListener(
            'keyboardDidHide',
            this._keyboardDidHide,
        )
        this.keyboardDidShowListener = Keyboard.addListener(
            'keyboardDidShow',
            this._keyboardDidShow,
        )
    }

    componentWillUnmount() {
        this.keyboardDidHideListener.remove();
        this.keyboardDidShowListener.remove();
    }

    _keyboardDidHide = () => {
        this.setState({editMode:false})
    }
    
    _keyboardDidShow = () => {
        if(this.state.inputOpen){
            this.setState({editMode:true})
        }
    }

    loginSaveData = async (dados) => {
        await SecureStore.setItemAsync('userToken', dados.userToken)
        await SecureStore.setItemAsync('tokenExpire', dados.tokenExpire.toString())
        await SecureStore.setItemAsync('userData', JSON.stringify(dados.userData))
    }

    login = () => {
        if(!this.state.email || !this.state.senha){
            this.setState({alert: {
                open: true,
                type: 'erro',
                title: 'Opa, opa...',
                msg: 'É obrigatório preencher o email e senha.',
            }})
		} else {
			this.setState({
				loading: true,
			})
			
			fetch('https://'+(global.beta ? 'beta.':'')+'entrey.com.br/_app/login/', {
				method: 'POST',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					'appID': 'TcCgSYD$w79r',
					'user': this.state.email,
					'pass': this.state.senha,
				}),
			})
			.then((response) => {
                return response.json()
            })
			.then((responseJson) => {
				switch(responseJson.status){
					case 'OK':
                        this.setState({
                            email: '',
                            senha: '',
                        })

                        this.loginSaveData({
                            userToken:responseJson.token,
                            tokenExpire:responseJson.expiration,
                            userData:responseJson.userData,
                        });
                        
                        this.props.signIn({
                            userToken : responseJson.token,
                            tokenExpire : responseJson.expiration,
                            userData : responseJson.userData,
                        })
					break;
					default:
						if(responseJson.message){
                            this.setState({alert: {
                                open: true,
                                type: 'erro',
                                title: 'Opa, opa...',
                                msg: responseJson.message,
                            }})
						}
						
						this.setState({
							loginIsHidden: false,
							loadingIsHidden: true,
						});
                }
                
                this.setState({
                    loading: false,
                })
			})
			.catch((error) => {
				console.error(error);
                
                this.setState({
                    loading: false,
                })
			})
		}
    }

    esqueceuSenha = () => {
        Linking.openURL('https://'+(global.beta ? 'beta.':'')+'entrey.com.br/forgot-password/')
    }

    render() {
        let logomarcaCont = null;

        if(!this.state.editMode){
            logomarcaCont = (
                <View style={styles.logoCont}>
                    <Image style={styles.image} source={logo} />
                    <Text style={styles.slogan}>Ingressos, tickets, serviços e produtos. 
{"\n"}Tudo descomplicado e sem mistério.</Text>
                </View>
            );
        } else {
            logomarcaCont = (
                <View style={styles.logoSimboloCont}>
                    <Image style={styles.image} source={logo_simbolo} />
                </View>
            );
        }

        setTimeout(() => {
            if(!this.state.inputOpen){
                this.setState({editMode:false})
            }
        }, 100)

        return (
            <ImageBackground source={background} style={styles.background}>
                <Loading loading={this.state.loading}></Loading>
                <CustomAlert options={this.state.alert}></CustomAlert>
                <View style={styles.conteiner}>
                    {logomarcaCont}
                    <View style={styles.formCont}>
                        <View style={styles.inputCont}>
                            <View style={styles.inputLine}>
                                <Icon name='user' size={20} color='#FFF' style={styles.icon} />
                                <TextInput placeholder='Email' style={styles.input}
                                    keyboardType='email-address' autoCapitalize='none'
                                    value={this.state.email} placeholderTextColor='#FFF'
                                    onChangeText={email => this.setState({email})}
                                    onFocus={() => this.setState({editMode:true,inputOpen:true})}
                                    onBlur={() => this.setState({inputOpen:false})}
                                />
                            </View>
                        </View>
                        <View style={styles.inputCont}>
                            <View style={styles.inputLine}>
                                <Icon name='lock' size={20} color='#FFF' style={styles.icon} />
                                <TextInput placeholder='Senha' style={styles.input}
                                    secureTextEntry={true} placeholderTextColor='#eeeeee'
                                    value={this.state.senha}  autoCapitalize='none'
                                    onChangeText={senha => this.setState({senha})}
                                    onFocus={() => this.setState({editMode:true,inputOpen:true})}
                                    onBlur={() => this.setState({inputOpen:false})}
                                />
                            </View>
                        </View>
                        <TouchableOpacity onPress={this.esqueceuSenha} style={styles.buttonEsqueceu}>
                            <Text style={styles.buttonTextEsqueceu}>Esqueceu sua senha?</Text>
                        </TouchableOpacity>
                        <TouchableOpacity onPress={this.login} style={styles.button}>
                            <LinearGradient useAngle={true} angle={220} angleCenter={{ x: 0.5, y: 0.5}} colors={['#ff2b67', '#ff5e3a']} style={styles.linearGradient}>
                                <Text style={styles.buttonText}>Login</Text>
                            </LinearGradient>
                        </TouchableOpacity>
                    </View>
                </View>
            </ImageBackground>
        );
    }
}

export default connect(null, { signIn })(SignInScreen)

const styles = StyleSheet.create({
    background:{
        width: '100%',
        height: '100%',
        position: "absolute",
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
    },
    conteiner:{
        paddingTop: Platform.OS === 'ios' ? 20 : 0,
        flex:1,
        justifyContent:"flex-start",
        alignItems: 'center',
        paddingLeft:30,
        paddingRight:30
    },
    logoCont: {
        width:'100%',
        flex:1,
        justifyContent:"flex-start",
        alignItems: 'center',
        marginTop:50,
    },
    logoSimboloCont: {
        width:'100%',
        flex:1,
        justifyContent:"center",
        alignItems: 'center',
    },
    formCont: {
        width:'100%',
        flex:2,
        justifyContent:"center",
        alignItems: 'center',
    },
    inputCont:{
        flexDirection:'row',
        justifyContent:'space-between',
        alignItems:'flex-start',
    },
    inputLine:{
        flex:1,
        flexDirection:'row',
        justifyContent:'space-between',
        alignItems:'flex-start',
        width:'100%',
        height:40,
        marginBottom:10,
        borderBottomColor: '#FFF',
        borderBottomWidth: 1,
    },
    slogan:{
        textAlign:'center',
        fontFamily: "OpenSans-Regular",
        fontSize:16,
        color: "#eeeeee",
        marginTop:10,
    },
    image: {
        width: 180,
        height:70,
        resizeMode: 'contain'
    },
    button:{
        width:'100%',
        height:50,
    },
    buttonEsqueceu:{
        width:'100%',
        height:40,
    },
    buttonText:{
        fontFamily: "OpenSans-Bold",
        fontWeight:'bold',
        color:'#FFF',
        fontSize:16,
    },
    buttonTextEsqueceu:{
        fontFamily: "OpenSans-Italic",
        color:'#eeeeee',
        fontSize:16,
    },
    input:{
        color:'#FFF',
        fontFamily: "OpenSans-Regular",
        fontSize:16,
        width:'100%'
    },
    icon:{
        marginTop:8,
        marginRight:10
    },
    linearGradient:{
        width:'100%',
        height:50,
        borderRadius:25,
        flex:1,
        justifyContent:"center",
        alignItems: 'center',
    }
});