import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hot_card/src/screen/home/category/product_by_category_screen.dart';
import 'package:hot_card/src/utils/app_tags.dart';
import 'package:get/get.dart';

import 'package:pull_to_refresh/pull_to_refresh.dart';

import '../../../models/all_category_product_model.dart';
import '../../../servers/repository.dart';
import '../../../utils/app_theme_data.dart';
import '../../../widgets/loader/shimmer_all_category.dart';

class AllCategory extends StatefulWidget {
  const AllCategory({Key? key}) : super(key: key);

  @override
  State<AllCategory> createState() => _AllCategoryState();
}

class _AllCategoryState extends State<AllCategory> {
  AllCategoryProductModel? allCategoryModel;

  //var allCategoryModel;
  int page = 1;
  RefreshController refreshController =
      RefreshController(initialRefresh: false);
  final GlobalKey refreshKey = GlobalKey();
  List<dynamic>? newcategories;
  List<dynamic>? filteredCategories;
  Future getAllCategory() async {
    allCategoryModel = await Repository().getAllCategoryContent(page: page);
    newcategories = allCategoryModel!.data!.categories;
    filteredCategories = newcategories!
        .where((category) =>
            category.slug != 'restornebi' && category.slug != 'gartoba')
        .toList();
    setState(() {});
  }

  @override
  void initState() {
    getAllCategory();
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return newcategories == null
        ? const ShimmerAllCategory()
        : newcategories!.isEmpty
            ? Container()
            : Scaffold(
                appBar: AppBar(
                  backgroundColor: Colors.transparent,
                  elevation: 0,
                  // leading: IconButton(
                  //   icon: const Icon(
                  //     Icons.arrow_back,
                  //     color: Colors.black,
                  //   ),
                  //   onPressed: () {
                  //     Get.back();
                  //   },
                  // ),
                  centerTitle: true,
                  title: Text(
                    AppTags.topObjects.tr,
                    style: AppThemeData.headerTextStyle_16,
                  ),
                ),
                body:
                    // allCategoryModel!.data != null
                    // ? SmartRefresher(
                    //     key: refreshKey,
                    //     controller: refreshController,
                    //     enablePullDown: true,
                    //     enablePullUp: true,
                    // physics: const BouncingScrollPhysics(),
                    // header: const WaterDropMaterialHeader(),
                    // footer: const ClassicFooter(
                    //   loadStyle: LoadStyle.ShowWhenLoading,
                    // ),
                    // child:
                    SingleChildScrollView(
                  scrollDirection: Axis.vertical,
                  child: NotificationListener<OverscrollIndicatorNotification>(
                    onNotification: (overscroll) {
                      overscroll
                          .disallowIndicator(); // This will prevent the overscroll glow effect
                      return false;
                    },
                    child: ListView.builder(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 15, vertical: 8),
                      shrinkWrap: true,
                      physics:
                          const ClampingScrollPhysics(), // Add this line to disable the scrolling physics of the ListView
                      itemCount: filteredCategories!.length,
                      itemBuilder: (context, index) {
                        return Center(
                          child: Column(
                            children: [
                              Padding(
                                padding: EdgeInsets.only(right: 0.w, left: 1.w),
                                child: InkWell(
                                  onTap: () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(
                                        builder: (_) => ProductByCategory(
                                          id: filteredCategories![index].id,
                                          title:
                                              filteredCategories![index].title,
                                          category: filteredCategories![index]
                                              .categoryFilter,
                                          number:
                                              filteredCategories![index].number,
                                          soc_fb:
                                              filteredCategories![index].soc_fb,
                                          soc_yt:
                                              filteredCategories![index].soc_yt,
                                          soc_in:
                                              filteredCategories![index].soc_in,
                                          imgurl:
                                              filteredCategories![index].banner,
                                          latlong: filteredCategories![index]
                                              .latlong,
                                        ),
                                      ),
                                    );
                                  },
                                  child: Column(
                                    children: [
                                      const SizedBox(height: 20),
                                      Container(
                                        width:
                                            MediaQuery.of(context).size.width,
                                        height: 155,
                                        decoration: BoxDecoration(
                                          borderRadius: const BorderRadius.all(
                                              Radius.circular(5)),
                                          image: DecorationImage(
                                            image: filteredCategories![index]
                                                        .banner !=
                                                    null
                                                ? NetworkImage(allCategoryModel!
                                                    .data!
                                                    .categories![index]
                                                    .banner!)
                                                : const NetworkImage(
                                                    'https://www.streamingmedia.com/Images/ArticleImages/ArticleImage.14143.jpg'),
                                            fit: BoxFit.cover,
                                          ),
                                        ),
                                      ),
                                      Padding(
                                        padding: const EdgeInsets.fromLTRB(
                                            8, 8, 0, 0),
                                        child: Align(
                                          alignment: Alignment.centerLeft,
                                          child: Text(
                                            filteredCategories![index]
                                                .title!
                                                .toString(),
                                            maxLines: 1,
                                            textAlign: TextAlign.left,
                                            style: AppThemeData
                                                .todayDealTitleStyle
                                                .copyWith(
                                              fontFamily: 'bpg',
                                              color: Colors.black,
                                              fontSize: 15,
                                              fontWeight: FontWeight.w600,
                                            ),
                                          ),
                                        ),
                                      ),
                                      Padding(
                                        padding: const EdgeInsets.fromLTRB(
                                            8, 3, 0, 0),
                                        child: Align(
                                          alignment: Alignment.centerLeft,
                                          child: Text(
                                            filteredCategories![index]
                                                        .categoryFilter
                                                        .toString() ==
                                                    'ტრადიციული'
                                                ? AppTags.traditional.tr
                                                : filteredCategories![index]
                                                            .categoryFilter
                                                            .toString() ==
                                                        'სუში'
                                                    ? AppTags.sushi.tr
                                                    : filteredCategories![index]
                                                                .categoryFilter
                                                                .toString() ==
                                                            'პიცა'
                                                        ? AppTags.pizza.tr
                                                        : filteredCategories![index]
                                                                    .categoryFilter
                                                                    .toString() ==
                                                                'ზღვის პროდუქტები'
                                                            ? AppTags.seafood.tr
                                                            : filteredCategories![index]
                                                                        .categoryFilter
                                                                        .toString() ==
                                                                    'ბურგერები'
                                                                ? AppTags
                                                                    .burgers.tr
                                                                : filteredCategories![index]
                                                                            .categoryFilter
                                                                            .toString() ==
                                                                        'აზიური'
                                                                    ? AppTags
                                                                        .asian
                                                                        .tr
                                                                    : filteredCategories![index].categoryFilter.toString() ==
                                                                            'საცხობი'
                                                                        ? AppTags
                                                                            .bakery
                                                                            .tr
                                                                        : filteredCategories![index].categoryFilter.toString() ==
                                                                                'დესერტი'
                                                                            ? AppTags.dessert.tr
                                                                            : filteredCategories![index].categoryFilter.toString() == 'მექსიკური'
                                                                                ? AppTags.mexican.tr
                                                                                : filteredCategories![index].categoryFilter.toString() == 'შაურმა'
                                                                                    ? AppTags.shawarma.tr
                                                                                    : AppTags.vegetarian.tr,
                                            maxLines: 1,
                                            textAlign: TextAlign.left,
                                            style: AppThemeData
                                                .todayDealTitleStyle
                                                .copyWith(
                                              fontFamily: 'bpg',
                                              color: const Color.fromARGB(
                                                  255, 78, 78, 78),
                                              fontSize: 12,
                                              fontWeight: FontWeight.w600,
                                            ),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ),
                ),
              );
  }
}