import * as React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import {useSelector, useDispatch} from 'react-redux';
import {
    createDrawerNavigator,
    DrawerContentScrollView,
    DrawerItem,
} from '@react-navigation/drawer';
import {
    View,
} from 'react-native'
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import * as SecureStore from 'expo-secure-store';
import RNBootSplash from "react-native-bootsplash";

import LoadingScreen from './screens/LoadingScreen';
import SignInScreen from './screens/SignInScreen';
import HomeScreen from './screens/HomeScreen';
import BaixarVoucher from './screens/BaixarVoucher';
import PerfilScreen from './screens/PerfilScreen';
import { restoreToken, appStarted, showCamera, hideCamera } from './redux/actions';

global.beta = true
global.screens = [
    {
        id:"BaixarVoucher",
        nome:"Baixar Voucher",
        icon:'vcard',
    },
    {
        id:"Perfil",
        nome:"Perfil",
        icon:'user',
    },
]

export default function BootStrap(){
    const {isLoading, isSignout, userToken} = useSelector(state => state.loginReducer);
    const dispatch = useDispatch();
    const actionAppStarted = () => dispatch(appStarted());
    const actionShowCamera = () => dispatch(showCamera());
    const actionHideCamera = () => dispatch(hideCamera());
    const actionRestoreToken = resData => dispatch(restoreToken(resData));
    
    React.useEffect(() => {
        const bootstrapAsync = async () => {
            // Pegar dados do disco

            let userTokenAux = await SecureStore.getItemAsync('userToken');
            let tokenExpire = await SecureStore.getItemAsync('tokenExpire');
            let userData = await SecureStore.getItemAsync('userData');

            // Ocultar tela de início do app.

            await RNBootSplash.hide({ fade: true });
            
            // Conferir se o token não está expirado

            if(userTokenAux && tokenExpire){
                const time = (Date.now()) / 1000;
                
                if(time < parseInt(tokenExpire)){
                    actionRestoreToken({
                        userToken: userTokenAux,
                        tokenExpire,
                        userData:JSON.parse(userData),
                    });
                } else {
                    actionAppStarted();
                }
            } else {
                actionAppStarted();
            }
        };

        bootstrapAsync();
    }, []);

    const Stack = createNativeStackNavigator();
    const Drawer = createDrawerNavigator();

    return (
        <NavigationContainer>
            {isLoading ? (
                <Stack.Navigator screenOptions={{headerShown: false}}>
                    <Stack.Screen name="Loading" component={LoadingScreen} />
                </Stack.Navigator>
            ) : userToken == null ? (
                <Stack.Navigator screenOptions={{headerShown: false}}>
                    <Stack.Screen name="SignIn" component={SignInScreen}
                        options={{
                            animationTypeForReplace: isSignout ? 'pop' : 'push',
                        }}
                    />
                </Stack.Navigator>
            ) : (
                <Drawer.Navigator
                    screenOptions={{headerShown: false}}
                    drawerContent={(props) => 
                        <DrawerContentScrollView {...props}>
                            <View>
                                <DrawerItem
                                    label='Home'
                                    onPress={() => props.navigation.navigate('Home')}
                                />
                            </View>
                            {global.screens.map(item => 
                                <View>
                                    <DrawerItem
                                        label={item.nome}
                                        onPress={() => {
                                            switch(item.id.toString()){
                                                case 'BaixarVoucher':
                                                    actionShowCamera();
                                                break;
                                                default:
                                                    actionHideCamera();
                                            }

                                            props.navigation.navigate(item.id.toString())
                                        }}
                                    />
                                </View>
                            )}
                        </DrawerContentScrollView>
                }>
                    <Drawer.Screen name="Home" component={HomeScreen} />
                    <Drawer.Screen name="BaixarVoucher" component={BaixarVoucher} />
                    <Drawer.Screen name="Perfil" component={PerfilScreen} />
                </Drawer.Navigator>
            )}
        </NavigationContainer>
    );
}