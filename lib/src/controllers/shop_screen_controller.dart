import 'package:get/get.dart';

import '../models/visit_shop_model.dart';
import '../servers/repository.dart';
import '../utils/constants.dart';

class ShopScreenController extends GetxController{
  Rx<VisitShopModel> visitShopModel = VisitShopModel().obs;

  var shopId = Get.parameters['shopId'];
  int page = 1;

  Future getVisitShop() async {
    printLog(shopId);
    visitShopModel.value = await Repository().getVisitShop(int.parse(shopId!));
  }

  @override
  void onInit() {
    getVisitShop();
    super.onInit();
  }

}