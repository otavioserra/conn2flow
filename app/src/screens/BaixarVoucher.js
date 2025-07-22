import React from 'react';
import {
    Platform,
    StyleSheet,
    View,
    Text,
    TouchableOpacity,
    ScrollView,
} from 'react-native';

import {connect} from 'react-redux';
import { signOut, renewToken, hideCamera } from '../redux/actions';

import Icon from 'react-native-vector-icons/FontAwesome'
import { CameraScreen } from 'react-native-camera-kit';
import Loading from '../components/Loading'
import CustomAlert from '../components/CustomAlert'
import Header from '../components/Header'
import Footer from '../components/Footer'

class BaixarVoucher extends React.Component {
    state = {
        opcao:'qrcode',
        loading: false,
        showCamera: true,
        alert: {
            open: false,
            type: '',
            title: '',
            msg: '',
        },
		servicoNome:'Nonono Nonono no Nonono Nonono',
		servicoLoteEVariacao:'Nonono Nonono no Nonono Nonono',
		qrcode:'Nonono Nonono no Nonono Nonono',
        voucher:{
            nome:'Nonono Nonono no Nonono Nonono',
            documento:'000.000.000-00',
            telefone:'(00) 00000-0000',
            codigo:'NONONONO'
        }
    }

    onQRCodeScanDone = (QRCode) => {
        this.setState({
            loading: true,
            showCamera: false,
            voucher:{
                nome:'',
                documento:'',
                telefone:'',
                codigo:''
            }
        })
		
		fetch('https://'+(global.beta ? 'beta.':'')+'entrey.com.br/_app/baixar-voucher/', {
			method: 'POST',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({
				'token': this.props.userToken,
				'opcao': 'verificar',
				'codigo': QRCode,
			}),
		})
        .then((response) => {
            return response.json()
        })
		.then((responseJson) => {
			switch(responseJson.status){
				case 'OK':
					this.setState({
                        opcao:'detalhes',
						servicoNome: responseJson.servicoNome,
						servicoLoteEVariacao: (responseJson.servicoLoteEVariacao ? responseJson.servicoLoteEVariacao : null),
                        qrcode: QRCode,
                        voucher:{
                            nome:responseJson.nome,
                            documento:responseJson.documento,
                            telefone:responseJson.telefone,
                            codigo:responseJson.voucherCodigo
                        }
					});
				break;
				case 'tokenExpired':
					this.props.signOut();
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
            }
			
			if(responseJson.tokenRenew){
				this.props.renewToken({
					userToken : responseJson.newToken,
					tokenExpire : responseJson.newExpiration,
				})
			}
            
            this.setState({
                loading: false,
            })
		})
		.catch((error) => {
            this.setState({
                loading: false,
                showCamera: true,
                alert: {
                    open: true,
                    type: 'erro',
                    title: 'Opa, opa...',
                    msg: error,
                }
            })
			console.error(error)
		})
    }
    
    baixar = () => {
		this.setState({
            loading: true,
		});
		
		fetch('https://'+(global.beta ? 'beta.':'')+'entrey.com.br/_app/baixar-voucher/', {
			method: 'POST',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
			},
			body: JSON.stringify({
				'token': this.props.userToken,
				'opcao': 'baixar',
				'codigo': this.state.qrcode,
			}),
		})
		.then((response) => response.json())
		.then((responseJson) => {
			this.setState({
				loading: false,
			});
			
			switch(responseJson.status){
				case 'OK':
					this.setState({
                        opcao:'qrcode',
                        alert: {
                            open: true,
                            type: 'ok',
                            title: 'Baixa',
                            msg: responseJson.message,
                        }
					});
				break;
				case 'tokenExpired':
					this.props.signOut();
				break;
				default:
					if(responseJson.message){
						this.setState({
                            opcao:'qrcode',
                            alert: {
                                open: true,
                                type: 'erro',
                                title: 'Baixa',
                                msg: responseJson.message,
                            }
                        });
					} else {
                        this.setState({
                            opcao:'qrcode',
                        });
                    }
			}
			
			if(responseJson.tokenRenew){
				this.props.renewToken({
					userToken : responseJson.newToken,
					tokenExpire : responseJson.newExpiration,
				})
			}
		})
		.catch((error) => {
            this.setState({
                loading: false,
                alert: {
                    open: true,
                    type: 'erro',
                    title: 'Opa, opa...',
                    msg: error,
                }
            });
			console.error(error);
		});
    }
    
    alertOkPressed = () => {
        switch(this.state.opcao){
            case 'qrcode':
                this.setState({
                    showCamera: true,
                })
            break;
        }
    }

    buttonBack = () => {
        this.setState({
            opcao:'qrcode',
            showCamera:true
        })
    }
  
    render() {
        let tela = null
        let menu = null
        let rodape = null
        let footer = null;

        //this.state.opcao = 'detalhes'

        switch(this.state.opcao){
            case 'detalhes':
                menu = (
                    <View style={styles.cabecalho}>
                        <View style={styles.title}>
                            <Icon name='th-list' size={25} color='#9b9b9b' style={styles.icon} />
                            <Text style={styles.text}>Detalhes do voucher {this.state.voucher.codigo}</Text>
                        </View>
                    </View>
                )

                tela = (
                    <View style={styles.detailsCont}>
                        <View style={styles.details}>
                            <ScrollView style={styles.detailsScroll}>
                                <View style={styles.detailsIn}>
                                    <View style={styles.status}>
                                        <Text style={styles.statusText}>Pago</Text>
                                    </View>
                                    <Text style={styles.nomeText}>Nome: {this.state.voucher.nome}</Text>
                                    <Text style={styles.telText}>Telefone: {this.state.voucher.telefone}</Text>
                                    <Text style={styles.docText}>Documento: {this.state.voucher.documento}</Text>
                                </View>
                            </ScrollView>
                        </View>
                    </View>
                )

                rodape = (
                    <View style={styles.rodapeCont}>
                        <TouchableOpacity onPress={this.baixar} style={styles.buttonConf}>
                            <Text style={styles.buttonTextConf}>Confirmar</Text>
                        </TouchableOpacity>
                        <TouchableOpacity onPress={this.buttonBack} style={styles.buttonBack}>
                            <Text style={styles.buttonTextBack}>Voltar</Text>
                        </TouchableOpacity>
                    </View>
                )

                
            break;
            default:
                let avatar = false;

                if(this.props.userData){
                    if(this.props.userData.avatar){
                        avatar = this.props.userData.avatar
                    }
                }

                menu = (
                    <View style={styles.cabecalho}>
                        <View style={styles.title}>
                            <Icon name='qrcode' size={25} color='#9b9b9b' style={styles.icon} />
                            <Text style={styles.text}>Leia o QR Code do voucher com a câmera traseira:</Text>
                        </View>
                    </View>
                )

                if(this.state.showCamera && this.props.cameraShow){
                    tela = (
                        <View style={styles.qrcodeCont}>
                            <CameraScreen
                                style={styles.qrcode}
                                scanBarcode={true}
                                onReadCode={event => this.onQRCodeScanDone(event.nativeEvent.codeStringValue)}
                            />
                        </View>
                    )
                } else {
                    tela = (
                        <View style={styles.qrcodeCont}>
                            
                        </View>
                    )
                }
                
                footer = (
                    <Footer navigation={this.props.navigation} hideCamera={this.props.hideCamera}></Footer>
                )
        }

        return (
            <View style={styles.conteiner}>
                <Loading loading={this.state.loading}></Loading>
                <CustomAlert options={this.state.alert} okPressed={this.alertOkPressed}></CustomAlert>
                <Header icon='vcard' title='Baixar voucher' navigation={this.props.navigation}></Header>
                <View style={styles.area}>
                    {menu}
                    {tela}
                    {rodape}
                </View>
                {footer}
            </View>
        )
    }
}

const mapStateToProps = state => {
    return {
        userData: state.loginReducer.userData,
        tokenExpire: state.loginReducer.tokenExpire,
        userToken: state.loginReducer.userToken,
        cameraShow: state.loginReducer.cameraShow
    }
}

export default connect(mapStateToProps, {signOut,renewToken,hideCamera})(BaixarVoucher)

const styles = StyleSheet.create({
    conteiner:{
        paddingTop: Platform.OS === 'ios' ? 20 : 0,
        flex:1,
        justifyContent:"flex-start",
        alignItems: 'center',
    },
    area:{
        flex:5,
        width:'100%',
        justifyContent:'flex-start',
        alignItems: 'center',
    },
    cabecalho:{
        flex:1,
        flexDirection:'row',
        justifyContent:"flex-start",
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
    qrcodeCont:{
        flex:7,
        flexDirection:'row',
        justifyContent:"flex-start",
        alignItems: 'center',
        width:'100%',
        height:'100%'
    },
    detailsCont:{
        flex:5,
        flexDirection:'row',
        justifyContent:"center",
        alignItems: 'center',
        width:'100%',
        height:'100%',
        backgroundColor:'#f4f5fa',
        paddingTop:0,
        paddingBottom:10,
        paddingLeft:20,
        paddingRight:20,
    },
    details:{
        flex:1,
        flexDirection:'row',
        justifyContent:"flex-start",
        alignItems: 'center',
        width:'100%',
        height:'100%',
        backgroundColor:'#FFF',
        borderRadius:15,
        shadowColor: "#140000",
        shadowOffset: {
            width: 0,
            height: 3,
        },
        shadowOpacity: 0.27,
        shadowRadius: 4.65,
        elevation: 6,
    },
    detailsScroll:{
        width:'100%',
        height:'100%',
    },
    detailsIn:{
        flex:1,
        justifyContent:"center",
        alignItems: 'flex-start',
        width:'100%',
        height:'100%',
        paddingTop:20,
        paddingBottom:20,
        paddingLeft:20,
        paddingRight:20,
    },
    rodapeCont:{
        justifyContent:"flex-start",
        alignItems: 'center',
        width:'100%',
        flex:2,
        backgroundColor:'#FFF',
        padding:20,
    },
    qrcode:{
        
    },
    title:{
        flex:1,
        flexDirection:'row',
        justifyContent:"flex-start",
        alignItems: 'center',
    },
    icon:{
        marginRight:10,
        marginTop:4,
    },
    text:{
        fontFamily: "OpenSans-Regular",
        fontSize:20,
        color:'#666666'
    },
    nomeText:{
        flex:1,
        fontFamily: "OpenSans-Bold",
        fontWeight:'bold',
        fontSize:20,
        marginTop:5,
        marginBottom:5,
        textAlign:'center',
        color:'#303030'
    },
    telText:{
        flex:1,
        fontFamily: "OpenSans-Regular",
        fontSize:20,
        color:'#707070'
    },
    docText:{
        flex:1,
        fontFamily: "OpenSans-Regular",
        marginTop:5,
        fontSize:20,
        color:'#707070'
    },
    button:{
        width:60,
        height:60,
        backgroundColor:'#6ae0bd',
        justifyContent:"center",
        alignItems: 'center',
        borderRadius:60,
    },
    buttonText:{
        fontFamily: "OpenSans-Regular",
        color:'#FFF',
        fontSize:16
    },
    buttonConf:{
        flex:1,
        width:'100%',
        height:50,
        backgroundColor:'#328ce6',
        justifyContent:"center",
        alignItems: 'center',
        borderRadius:30,
        marginBottom:10,
    },
    buttonTextConf:{
        fontFamily: "OpenSans-Bold",
        fontWeight:'bold',
        color:'#FFF',
        fontSize:18
    },
    buttonBack:{
        flex:1,
        marginTop:5,
        width:'100%',
        height:50,
        backgroundColor:'#dedede',
        justifyContent:"center",
        alignItems: 'center',
        borderRadius:30
    },
    buttonTextBack:{
        fontFamily: "OpenSans-Bold",
        fontWeight:'bold',
        color:'#848484',
        fontSize:18
    },
    status:{
        backgroundColor:'#d1f0cd',
        justifyContent:"center",
        alignItems: 'center',
        width:90,
        height:23,
        borderRadius:20,
    },
    statusText:{
        fontFamily: "OpenSans-Regular",
        fontSize:14,
        color:'#353d34',
    }
})